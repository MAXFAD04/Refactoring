<?php

namespace App\DTO;

/**
 * Data Transfer Object для данных МКС
 */
final class IssDataDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?float $altitude,
        public readonly ?float $velocity,
        public readonly ?string $fetchedAt,
        public readonly array $payload = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        $payload = $data['payload'] ?? [];
        
        return new self(
            id: $data['id'] ?? null,
            latitude: isset($payload['latitude']) ? (float)$payload['latitude'] : null,
            longitude: isset($payload['longitude']) ? (float)$payload['longitude'] : null,
            altitude: isset($payload['altitude']) ? (float)$payload['altitude'] : null,
            velocity: isset($payload['velocity']) ? (float)$payload['velocity'] : null,
            fetchedAt: $data['fetched_at'] ?? null,
            payload: $payload
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'altitude' => $this->altitude,
            'velocity' => $this->velocity,
            'fetched_at' => $this->fetchedAt,
            'payload' => $this->payload,
        ];
    }
}

