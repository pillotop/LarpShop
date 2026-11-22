<?php

namespace Database\Seeders\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;

/**
 * Shared deterministic context and reference date anchor for demo seeding.
 */
class DemoSeedContext
{
    private ?CarbonImmutable $anchor = null;

    private ?string $hashedDemoPassword = null;

    private int $rngState = 20261122;

    /**
     * Return the canonical end-of-horizon reference timestamp for all seeded timelines.
     */
    public function anchor(): CarbonImmutable
    {
        if ($this->anchor === null) {
            $configured = config('seeding.anchor');
            $base = $configured ? CarbonImmutable::parse($configured) : CarbonImmutable::now();
            $this->anchor = $base->startOfHour();
        }

        return $this->anchor;
    }

    /**
     * Cache the hashed demo password once per process so seeding stays fast in SQLite tests.
     */
    public function demoPasswordHash(): string
    {
        return $this->hashedDemoPassword ??= Hash::make('password123');
    }

    /**
     * Return a deterministic integer within [$min, $max] based on a string key.
     */
    public function deterministicInt(string $key, int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }

        $hash = sprintf('%u', crc32($key.'|'.$this->rngState));
        $span = $max - $min + 1;

        return $min + ((int) $hash % $span);
    }

    /**
     * Pick a deterministic element from a non-empty list using a stable string key.
     *
     * @template TValue
     *
     * @param  array<int, TValue>  $items
     * @return TValue
     */
    public function pick(string $key, array $items): mixed
    {
        $index = $this->deterministicInt($key, 0, count($items) - 1);

        return $items[$index];
    }
}
