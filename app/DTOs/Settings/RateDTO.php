<?php

namespace App\DTOs\Settings;

use Illuminate\Support\Carbon;

readonly class RateDTO
{
    public function __construct(
        public Carbon $rateDate,
        public float $rateUsd,
        public float $rateCop,
        public float $rateEur,
        public float $rateCopBs = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            rateDate: Carbon::parse($data['rate_date']),
            rateUsd: (float) $data['rate_usd'],
            rateCop: (float) $data['rate_cop'],
            rateEur: (float) $data['rate_eur'],
            rateCopBs: (float) ($data['rate_cop_bs'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'rate_date' => $this->rateDate->format('Y-m-d'),
            'rate_usd' => $this->rateUsd,
            'rate_cop' => $this->rateCop,
            'rate_eur' => $this->rateEur,
            'rate_cop_bs' => $this->rateCopBs,
        ];
    }
}
