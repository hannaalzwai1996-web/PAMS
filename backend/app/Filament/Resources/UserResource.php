<?php

namespace App\Filament\Resources;

use App\Domain\User\DTOs\SyncPermissionsDTO;
use App\Domain\User\DTOs\UserDTO;
use App\Domain\User\Services\UserService;
use App\Filament\Concerns\HandlesDomainExceptions;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Enums\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;

/**
 * "Manage users" (ADR-0001 §3). Every mutation here — create, update,
 * role assignment, permission sync, deactivate, delete — goes through
 * UserService, exactly the same Service the REST API's
 * Admin\UserController uses. This resource contains no business rules of
 * its own: uniqueness/format checks are ordinary form validation, and
 * every actual decision (self-deactivation guard, token/session
 * revocation, "exactly one role") is enforced inside the Service either
 * way, so Filament can't bypass it even if this file got the logic wrong.
 */
class UserResource extends Resource
{
    use HandlesDomainExceptions;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Account')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('email')
                        ->required()
                        ->email()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::defaults())
                        ->visibleOn('create'),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Deactivating immediately revokes this user\'s active sessions and API tokens.'),
                ]),
            Section::make('Role')
                ->schema([
                    Select::make('role')
                        ->label('Role')
                        ->options([
                            Role::Admin->value => 'Admin',
                            Role::QualityAssuranceOfficer->value => 'Quality Assurance Officer',
                            Role::ProgramCoordinator->value => 'Program Coordinator',
                        ])
                        ->default(fn (?User $record) => $record?->roles->first()?->name)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title())
                    ->colors([
                        'warning' => 'admin',
                        'info' => 'qa_officer',
                        'success' => 'program_coordinator',
                    ]),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role'),
            ])
            ->actions([
                EditAction::make(),
                static::managePermissionsAction(),
                static::deactivateAction(),
                static::activateAction(),
                DeleteAction::make()
                    ->action(function (User $record) {
                        static::deleteViaService($record);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(fn (User $record) => static::deleteViaService($record));
                        }),
                ]),
            ]);
    }

    /**
     * Direct permission grants, independent of role — mirrors the REST
     * API's PUT /admin/users/{user}/permissions (UserService::syncPermissions()).
     */
    protected static function managePermissionsAction(): Action
    {
        return Action::make('managePermissions')
            ->label('Permissions')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->form([
                CheckboxList::make('permissions')
                    ->label('Direct permissions (in addition to role)')
                    ->options(fn () => Permission::pluck('name', 'name'))
                    ->columns(2),
            ])
            ->fillForm(fn (User $record) => ['permissions' => $record->getDirectPermissions()->pluck('name')->all()])
            ->action(function (User $record, array $data) {
                app(UserService::class)->syncPermissions(
                    $record,
                    SyncPermissionsDTO::fromArray(['permissions' => $data['permissions']]),
                );

                Notification::make()->title('Permissions updated.')->success()->send();
            });
    }

    /**
     * Mirrors the REST API's POST /admin/users/{user} deactivate path —
     * calls UserService::update(), which enforces the self-deactivation
     * guard and revokes tokens/sessions (ARCH-0001 §3).
     */
    protected static function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (User $record) => $record->is_active)
            ->action(function (User $record) {
                static::runOrNotify(function () use ($record) {
                    app(UserService::class)->update(
                        $record,
                        UserDTO::fromArray(['is_active' => false]),
                        auth()->user(),
                    );
                });

                Notification::make()->title('User deactivated.')->success()->send();
            });
    }

    protected static function activateAction(): Action
    {
        return Action::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn (User $record) => ! $record->is_active)
            ->action(function (User $record) {
                app(UserService::class)->update(
                    $record,
                    UserDTO::fromArray(['is_active' => true]),
                    auth()->user(),
                );

                Notification::make()->title('User activated.')->success()->send();
            });
    }

    protected static function deleteViaService(User $record): void
    {
        static::runOrNotify(function () use ($record) {
            app(UserService::class)->delete($record, auth()->user());
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
