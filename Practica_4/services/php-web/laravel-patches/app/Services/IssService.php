<?php

namespace App\Services;

use App\DTO\IssDataDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service для работы с данными МКС
 * Инкапсулирует логику взаимодействия с Rust сервисом
 */
class IssService
{
    private const TIMEOUT = 10; // секунды
    private const RETRY_TIMES = 2;
    private const RETRY_DELAY = 500; // миллисекунды

    public function __construct(
        private readonly string $rustBaseUrl
    ) {
    }

    /**
     * Получить последние данные МКС
     */
    public function getLastData(): ?IssDataDTO
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->retry(self::RETRY_TIMES, self::RETRY_DELAY)
                ->get($this->rustBaseUrl . '/last');

            if ($response->successful()) {
                $data = $response->json();
                return IssDataDTO::fromArray($data);
            }

            Log::warning('ISS service: failed to get last data', [
                'status' => $response->status(),
                'url' => $this->rustBaseUrl . '/last'
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('ISS service error', [
                'method' => 'getLastData',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить данные о тренде движения МКС
     */
    public function getTrendData(): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->retry(self::RETRY_TIMES, self::RETRY_DELAY)
                ->get($this->rustBaseUrl . '/iss/trend');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('ISS service: failed to get trend data', [
                'status' => $response->status()
            ]);

            return [];
        } catch (\Throwable $e) {
            Log::error('ISS service error', [
                'method' => 'getTrendData',
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Триггер принудительного обновления данных МКС
     */
    public function triggerFetch(): ?IssDataDTO
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get($this->rustBaseUrl . '/fetch');

            if ($response->successful()) {
                $data = $response->json();
                return IssDataDTO::fromArray($data);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('ISS service error', [
                'method' => 'triggerFetch',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

