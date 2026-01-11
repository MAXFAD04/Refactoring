<?php

namespace App\DTO;

/**
 * Data Transfer Object для метрик дашборда
 */
final class DashboardMetricsDTO
{
    public function __construct(
        public readonly ?float $issSpeed,
        public readonly ?float $issAltitude,
        public readonly int $neoTotal = 0
    ) {
    }

    public static function fromIssData(?IssDataDTO $issData): self
    {
        return new self(
            issSpeed: $issData?->velocity,
            issAltitude: $issData?->altitude,
            neoTotal: 0
        );
    }

    public function toArray(): array
    {
        return [
            'iss_speed' => $this->issSpeed,
            'iss_alt' => $this->issAltitude,
            'neo_total' => $this->neoTotal,
        ];
    }
}

