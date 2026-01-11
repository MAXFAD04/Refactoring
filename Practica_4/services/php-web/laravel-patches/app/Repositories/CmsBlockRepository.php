<?php

namespace App\Repositories;

use App\DTO\CmsBlockDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Repository паттерн для работы с CMS блоками
 * Отделяет слой доступа к данным от бизнес-логики
 * Обеспечивает единую точку работы с БД и кэшированием
 */
class CmsBlockRepository
{
    private const CACHE_TTL = 3600; // 1 час
    private const CACHE_PREFIX = 'cms_block:';

    /**
     * Найти активный блок по slug
     */
    public function findActiveBySlug(string $slug): ?CmsBlockDTO
    {
        $cacheKey = self::CACHE_PREFIX . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            $row = DB::table('cms_blocks')
                ->where('slug', '=', $slug)
                ->where('is_active', '=', true)
                ->first();

            if (!$row) {
                return null;
            }

            return CmsBlockDTO::fromArray((array)$row);
        });
    }

    /**
     * Найти все активные блоки
     */
    public function findAllActive(): array
    {
        $rows = DB::table('cms_blocks')
            ->where('is_active', '=', true)
            ->orderBy('slug')
            ->get();

        return array_map(
            fn($row) => CmsBlockDTO::fromArray((array)$row),
            $rows->toArray()
        );
    }

    /**
     * Инвалидация кэша для блока
     */
    public function clearCache(string $slug): void
    {
        Cache::forget(self::CACHE_PREFIX . $slug);
    }

    /**
     * Инвалидация всего кэша CMS блоков
     */
    public function clearAllCache(): void
    {
        Cache::flush(); // В продакшене использовать тегированный кэш
    }
}

