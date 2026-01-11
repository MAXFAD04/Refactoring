<?php

namespace App\Services;

use App\Support\JwstHelper;
use Illuminate\Support\Facades\Log;

/**
 * Service для работы с JWST API
 * Обеспечивает тестируемость через DI и централизованную обработку ошибок
 */
class JwstService
{
    public function __construct(
        private readonly JwstHelper $helper
    ) {
    }

    /**
     * Получить фид изображений JWST
     */
    public function getFeed(array $params): array
    {
        try {
            $source = $params['source'] ?? 'jpg';
            $suffix = trim($params['suffix'] ?? '');
            $program = trim($params['program'] ?? '');
            $instrumentFilter = strtoupper(trim($params['instrument'] ?? ''));
            $page = max(1, (int)($params['page'] ?? 1));
            $perPage = max(1, min(60, (int)($params['perPage'] ?? 24)));

            // Выбираем эндпоинт
            $path = $this->selectEndpoint($source, $suffix, $program);

            // Получаем данные
            $response = $this->helper->get($path, [
                'page' => $page,
                'perPage' => $perPage
            ]);

            $list = $response['body'] ?? ($response['data'] ?? (is_array($response) ? $response : []));

            // Нормализуем данные
            $items = $this->normalizeItems($list, $instrumentFilter, $perPage);

            return [
                'source' => $path,
                'count' => count($items),
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            Log::error('JWST service error', [
                'method' => 'getFeed',
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            return [
                'source' => '',
                'count' => 0,
                'items' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Выбрать эндпоинт API на основе параметров
     */
    private function selectEndpoint(string $source, string $suffix, string $program): string
    {
        if ($source === 'suffix' && $suffix !== '') {
            return 'all/suffix/' . ltrim($suffix, '/');
        }

        if ($source === 'program' && $program !== '') {
            return 'program/id/' . rawurlencode($program);
        }

        return 'all/type/jpg';
    }

    /**
     * Нормализовать элементы фида
     */
    private function normalizeItems(array $list, string $instrumentFilter, int $perPage): array
    {
        $items = [];

        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }

            // Находим URL изображения
            $url = $this->findImageUrl($item);
            if (!$url) {
                continue;
            }

            // Получаем список инструментов
            $instruments = $this->extractInstruments($item);

            // Фильтруем по инструменту
            if ($instrumentFilter && $instruments && !in_array($instrumentFilter, $instruments, true)) {
                continue;
            }

            $items[] = [
                'url' => $url,
                'obs' => (string)($item['observation_id'] ?? $item['observationId'] ?? ''),
                'program' => (string)($item['program'] ?? ''),
                'suffix' => (string)($item['details']['suffix'] ?? $item['suffix'] ?? ''),
                'inst' => $instruments,
                'caption' => $this->buildCaption($item, $instruments),
                'link' => $item['location'] ?? $url,
            ];

            if (count($items) >= $perPage) {
                break;
            }
        }

        return $items;
    }

    /**
     * Найти URL изображения
     */
    private function findImageUrl(array $item): ?string
    {
        $location = $item['location'] ?? $item['url'] ?? null;
        $thumbnail = $item['thumbnail'] ?? null;

        foreach ([$location, $thumbnail] as $url) {
            if (is_string($url) && preg_match('~\.(jpg|jpeg|png)(\?.*)?$~i', $url)) {
                return $url;
            }
        }

        return JwstHelper::pickImageUrl($item);
    }

    /**
     * Извлечь список инструментов
     */
    private function extractInstruments(array $item): array
    {
        $instruments = [];
        
        foreach (($item['details']['instruments'] ?? []) as $inst) {
            if (is_array($inst) && !empty($inst['instrument'])) {
                $instruments[] = strtoupper($inst['instrument']);
            }
        }

        return $instruments;
    }

    /**
     * Построить caption для изображения
     */
    private function buildCaption(array $item, array $instruments): string
    {
        $parts = [];

        $obsId = $item['observation_id'] ?? $item['id'] ?? '';
        if ($obsId) {
            $parts[] = $obsId;
        }

        $program = $item['program'] ?? '';
        $parts[] = 'P' . ($program ?: '-');

        $suffix = $item['details']['suffix'] ?? $item['suffix'] ?? '';
        if ($suffix) {
            $parts[] = $suffix;
        }

        if ($instruments) {
            $parts[] = implode('/', $instruments);
        }

        return implode(' · ', $parts);
    }
}

