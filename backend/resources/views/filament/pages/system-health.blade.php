<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($checks as $label => $result)
            <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</span>

                    @php
                        $color = match ($result['status']) {
                            'ok' => 'success',
                            'error' => 'danger',
                            default => 'gray',
                        };
                    @endphp

                    <x-filament::badge :color="$color">
                        {{ ucfirst($result['status']) }}
                    </x-filament::badge>
                </div>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $result['message'] }}
                </p>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
