<?php

namespace App\Support\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Backs the "Monitor system" admin capability (ADR-0001 §3). Cross-cutting
 * infrastructure, not tied to any of the five academic domains — lives in
 * Support/ alongside Enums/Exceptions, same rationale as those.
 */
class SystemHealthCheckService
{
    /**
     * @return array<string, array{status: 'ok'|'error'|'info', message: string}>
     */
    public function check(): array
    {
        return [
            'Database' => $this->checkDatabase(),
            'Cache' => $this->checkCache(),
            'Storage' => $this->checkStorage(),
            'Queue' => $this->checkQueue(),
        ];
    }

    /**
     * @return array{status: 'ok'|'error', message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Connected via '.DB::connection()->getDriverName()];
        } catch (Throwable $exception) {
            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: 'ok'|'error', message: string}
     */
    private function checkCache(): array
    {
        try {
            $key = 'pams-health-check-'.Str::random(8);
            Cache::put($key, true, 5);
            $ok = Cache::pull($key) === true;

            return [
                'status' => $ok ? 'ok' : 'error',
                'message' => $ok ? 'Read/write ok ('.config('cache.default').')' : 'Cache did not round-trip a written value',
            ];
        } catch (Throwable $exception) {
            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: 'ok'|'error', message: string}
     */
    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default'));
            $path = 'health-check-'.Str::random(8).'.txt';
            $disk->put($path, 'ok');
            $ok = $disk->exists($path);
            $disk->delete($path);

            return [
                'status' => $ok ? 'ok' : 'error',
                'message' => $ok ? 'Writable ('.config('filesystems.default').')' : 'Write/read check failed',
            ];
        } catch (Throwable $exception) {
            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: 'info', message: string}
     */
    private function checkQueue(): array
    {
        return ['status' => 'info', 'message' => 'Using ['.config('queue.default').'] connection'];
    }
}
