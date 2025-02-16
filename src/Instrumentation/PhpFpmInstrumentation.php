<?php

namespace Keepsuit\LaravelOpenTelemetry\Instrumentation;

use Keepsuit\LaravelOpenTelemetry\Facades\Meter;

class PhpFpmInstrumentation implements Instrumentation
{
    /**
     * @param array{
     *     enabled?: string|bool,
     *     prefix?: string,
     *     pool?: string|bool,
     *     processes?: string|bool,
     * } $options
     * @return void
     */
    public function register(array $options): void
    {
        if (!function_exists('fpm_get_status')) {
            return;
        }

        $fpmStatus = fpm_get_status();
        if ($fpmStatus === false) {
            return;
        }

        $prefix = $options['prefix'] ?? 'php_fpm_';

        $attributes = [
            'php.fpm.pool' => $fpmStatus['pool'] ?? 'unknown',
            'php.fpm.pm' => $fpmStatus['process-manager'] ?? 'unknown',
        ];


        if ($options['pool'] ?? true) {
            $o = Meter::createGauge(
                $prefix . 'pools',
                "pool",
                'The status of the PHP-FPM pool'
            );

            $o->record($fpmStatus['start-time'] ?? -1, ['name' => 'start_time', ...$attributes]);
            $o->record($fpmStatus['start-since'] ?? -1, ['name' => 'start_since', ...$attributes]);
            $o->record($fpmStatus['accepted-conn'] ?? -1, ['name' => 'accepted_conn', ...$attributes]);
            $o->record($fpmStatus['listen-queue'] ?? -1, ['name' => 'listen_queue', ...$attributes]);
            $o->record($fpmStatus['max-listen-queue'] ?? -1, ['name' => 'max_listen_queue', ...$attributes]);
            $o->record($fpmStatus['listen-queue-len'] ?? -1, ['name' => 'listen_queue_len', ...$attributes]);
            $o->record($fpmStatus['idle-processes'] ?? -1, ['name' => 'processes', 'state' => 'idle', ...$attributes]);
            $o->record($fpmStatus['active-processes'] ?? -1, ['name' => 'processes', 'state' => 'active', ...$attributes]);
            $o->record($fpmStatus['total-processes'] ?? -1, ['name' => 'total_processes', ...$attributes]);
            $o->record($fpmStatus['max-active-processes'] ?? -1, ['name' => 'max_active_processes', ...$attributes]);
            $o->record($fpmStatus['max-children-reached'] ?? -1, ['name' => 'max_children_reached', ...$attributes]);
            $o->record($fpmStatus['slow-requests'] ?? -1, ['name' => 'slow_requests', ...$attributes]);
            $o->record($fpmStatus['memory-peak'] ?? -1, ['name' => 'memory_peak', ...$attributes]);
        }

        if ($options['processes'] ?? true) {
            $o = Meter::createGauge(
                $prefix . 'pool_processes',
                "process",
                'The status of the PHP-FPM pool processes'
            );

            foreach ($fpmStatus['procs'] ?? [] as $proc) {
                $attributes = [
                    'php.fpm.pool' => $fpmStatus['pool'] ?? 'unknown',
                    'php.fpm.pm' => $fpmStatus['process-manager'] ?? 'unknown',
                    'php.fpm.process.id' => $proc['pid'] ?? 'unknown',
                    'php.fpm.process.state' => $proc['state'] ?? 'unknown',
                    'php.fpm.process.request.method' => $proc['request-method'] ?? 'unknown',
                    'php.fpm.process.request.uri' => $proc['request-uri'] ?? 'unknown',
                    'php.fpm.process.request.query.string' => $proc['query-string'] ?? 'unknown',
                    'php.fpm.process.user' => $proc['user'] ?? 'unknown',
                    'php.fpm.process.script' => $proc['script'] ?? 'unknown',
                ];

                $o->record($proc['start-time'] ?? -1, ['name' => 'start_time', ...$attributes]);
                $o->record($proc['start-since'] ?? -1, ['name' => 'start_since', ...$attributes]);
                $o->record($proc['requests'] ?? -1, ['name' => 'requests', ...$attributes]);
                $o->record($proc['request-duration'] ?? -1, ['name' => 'request_duration', ...$attributes]);
                $o->record($proc['request-length'] ?? -1, ['name' => 'request_length', ...$attributes]);
                $o->record($proc['last-request-cpu'] ?? -1, ['name' => 'last_request_cpu', ...$attributes]);
                $o->record($proc['last-request-memory'] ?? -1, ['name' => 'last_request_memory', ...$attributes]);
            }
        }
    }

}
