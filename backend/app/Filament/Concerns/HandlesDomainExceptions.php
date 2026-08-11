<?php

namespace App\Filament\Concerns;

use App\Support\Exceptions\DomainException;
use Closure;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

/**
 * The Filament-side counterpart to App\Support\Exceptions\Handlers\ApiExceptionRenderer:
 * turns a DomainException raised by a Service into a user-facing
 * notification instead of a stack trace. This is UI plumbing, not
 * business logic — it doesn't decide anything, it just displays what the
 * Service already decided (ADR-0001 §3).
 */
trait HandlesDomainExceptions
{
    /**
     * Static (not instance) because it's called both from Resource-level
     * static Action closures and from Page-level instance hook methods
     * (handleRecordCreation/handleRecordUpdate) — PHP allows calling a
     * static method via `$this->` too, so one implementation serves both.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected static function runOrNotify(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (DomainException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }
}
