<?php

namespace App\Data\Reports;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ReportPeriod
{
    public const RANGES = ['7_days', '30_days', 'this_month', 'all'];

    private function __construct(
        public string $range,
        public ?CarbonImmutable $since,
        public CarbonImmutable $until,
        public bool $endInclusive = true,
    ) {}

    public static function forRange(string $range): self
    {
        $until = CarbonImmutable::now();
        $since = match ($range) {
            '7_days' => $until->subDays(7),
            '30_days' => $until->subDays(30),
            'this_month' => $until->startOfMonth(),
            'all' => null,
            default => throw new InvalidArgumentException('Unsupported report period.'),
        };

        return new self($range, $since, $until);
    }

    public function previous(): ?self
    {
        if ($this->since === null || $this->since->equalTo($this->until)) {
            return null;
        }

        $duration = (int) $this->since->diffInMicroseconds($this->until);

        return new self(
            $this->range,
            $this->since->subMicroseconds($duration),
            $this->since,
            endInclusive: false,
        );
    }
}
