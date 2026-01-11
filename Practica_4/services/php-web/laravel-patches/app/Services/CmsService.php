<?php

namespace App\Services;

use App\Repositories\CmsBlockRepository;
use App\DTO\CmsBlockDTO;

/**
 * Service для работы с CMS блоками
 * Содержит бизнес-логику и обработку ошибок
 */
class CmsService
{
    public function __construct(
        private readonly CmsBlockRepository $repository
    ) {
    }

    /**
     * Получить контент блока по slug или вернуть fallback
     */
    public function getBlockContent(string $slug, string $fallback = ''): string
    {
        try {
            $block = $this->repository->findActiveBySlug($slug);
            return $block?->content ?? $fallback;
        } catch (\Throwable $e) {
            \Log::error('CMS block fetch error', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return $fallback;
        }
    }

    /**
     * Получить DTO блока по slug
     */
    public function getBlock(string $slug): ?CmsBlockDTO
    {
        try {
            return $this->repository->findActiveBySlug($slug);
        } catch (\Throwable $e) {
            \Log::error('CMS block fetch error', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получить все активные блоки
     */
    public function getAllActiveBlocks(): array
    {
        try {
            return $this->repository->findAllActive();
        } catch (\Throwable $e) {
            \Log::error('CMS blocks fetch error', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}

