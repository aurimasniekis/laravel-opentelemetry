<?php

namespace Keepsuit\LaravelOpenTelemetry\Instrumentation;

use Keepsuit\LaravelOpenTelemetry\Facades\Meter;

class PhpOpcacheInstrumentation implements Instrumentation
{
    /**
     * @param array{
     *     enabled?: string|bool,
     *     prefix?: string,
     *     scripts?: string|bool,
     * } $options
     * @return void
     */
    public function register(array $options): void
    {
        if (!function_exists('opcache_get_status')) {
            return;
        }

        $opcacheStatus = opcache_get_status();
        if ($opcacheStatus === false) {
            return;
        }

        $prefix = $options['prefix'] ?? 'php_opcache_';

        $jit = $opcacheStatus['jit'] ?? [];
        $attributes = [
            'php.opcache.jit.enabled' => $jit['enabled'] ?? 'unknown',
            'php.opcache.jit.on' => $jit['on'] ?? 'unknown',
            'php.opcache.jit.kind' => $jit['kind'] ?? 'unknown',
            'php.opcache.jit.opt_level' => $jit['opt_level'] ?? 'unknown',
            'php.opcache.jit.opt_flags' => $jit['opt_flags'] ?? 'unknown',
        ];

        $o = Meter::createGauge(
            $prefix . 'memory',
            "bytes",
            'The memory usage of the PHP Opcache'
        );

        $memory = $opcacheStatus['memory_usage'] ?? [];
        $o->record($memory['used_memory'] ?? -1, ['name' => 'memory', 'state' => 'used', ...$attributes]);
        $o->record($memory['free_memory'] ?? -1, ['name' => 'memory', 'state' => 'free', ...$attributes]);
        $o->record($memory['wasted_memory'] ?? -1, ['name' => 'memory', 'state' => 'wasted', ...$attributes]);
        $o->record($memory['wasted_memory'] ?? -1, ['name' => 'memory', 'state' => 'wasted', ...$attributes]);
        $o->record($memory['current_wasted_percentage'] ?? -1, ['name' => 'current_wasted_percentage', ...$attributes]);

        $o = Meter::createGauge(
            $prefix . 'interned_strings',
            "bytes",
            'The memory usage of the PHP Opcache interned strings'
        );

        $memory = $opcacheStatus['interned_strings_usage'] ?? [];
        $o->record($memory['used_memory'] ?? -1, ['name' => 'memory', 'state' => 'used', ...$attributes]);
        $o->record($memory['free_memory'] ?? -1, ['name' => 'memory', 'state' => 'free', ...$attributes]);
        $o->record($memory['buffer_size'] ?? -1, ['name' => 'buffer_size', ...$attributes]);
        $o->record($memory['number_of_strings'] ?? -1, ['name' => 'number_of_strings', ...$attributes]);

        $o = Meter::createGauge(
            $prefix . 'statistics',
            null,
            'The statistics of the PHP Opcache'
        );

        $stats = $opcacheStatus['opcache_statistics'] ?? [];
        $o->record($stats['num_cached_scripts'] ?? -1, ['name' => 'cached_scripts', ...$attributes]);
        $o->record($stats['num_cached_keys'] ?? -1, ['name' => 'cached_keys', ...$attributes]);
        $o->record($stats['max_cached_keys'] ?? -1, ['name' => 'max_cached_keys', ...$attributes]);
        $o->record($stats['hits'] ?? -1, ['name' => 'hits', ...$attributes]);
        $o->record($stats['start_time'] ?? -1, ['name' => 'start_time', ...$attributes]);
        $o->record($stats['last_restart_time'] ?? -1, ['name' => 'last_restart_time', ...$attributes]);
        $o->record($stats['oom_restarts'] ?? -1, ['name' => 'restarts', 'type' => 'oom', ...$attributes]);
        $o->record($stats['hash_restarts'] ?? -1, ['name' => 'restarts', 'type' => 'hash', ...$attributes]);
        $o->record($stats['manual_restarts'] ?? -1, ['name' => 'restarts', 'type' => 'manual', ...$attributes]);
        $o->record($stats['misses'] ?? -1, ['name' => 'misses', ...$attributes]);
        $o->record($stats['blacklist_misses'] ?? -1, ['name' => 'blacklist_misses', ...$attributes]);
        $o->record($stats['blacklist_miss_ratio'] ?? -1, ['name' => 'blacklist_miss_ratio', ...$attributes]);
        $o->record($stats['opcache_hit_rate'] ?? -1, ['name' => 'opcache_hit_rate', ...$attributes]);

        $o = Meter::createGauge(
            $prefix . 'jit',
            null,
            'The status of the PHP Opcache JIT'
        );

        $o->record($status['buffer_size'] ?? -1, ['name' => 'buffer_size', ...$attributes]);
        $o->record($status['buffer_free'] ?? -1, ['name' => 'buffer_free', ...$attributes]);

        if ($options['scripts'] ?? true) {
            $o = Meter::createGauge(
                $prefix . 'scripts',
                null,
                'The status of the PHP Opcache scripts'
            );

            $scripts = $opcacheStatus['scripts'] ?? [];
            foreach ($scripts as $script) {
                $scriptAttrs = [
                    'php.opcache.script.full_path' => $script['full_path'] ?? 'unknown',
                    ...$attributes,
                ];

                $o->record($script['hits'] ?? -1, ['name' => 'hits', ...$scriptAttrs]);
                $o->record($script['memory_consumption'] ?? -1, ['name' => 'memory_consumption', ...$scriptAttrs]);
                $o->record($script['last_used_timestamp'] ?? -1, ['name' => 'last_used_timestamp', ...$scriptAttrs]);
                $o->record($script['revalidate'] ?? -1, ['name' => 'revalidate', ...$scriptAttrs]);

            }
        }
    }
}
