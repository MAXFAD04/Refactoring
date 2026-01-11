<?php

namespace App\DTO;

/**
 * Data Transfer Object для CMS блока
 * Обеспечивает типизацию и валидацию данных между слоями
 */
final class CmsBlockDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $slug,
        public readonly string $content,
        public readonly bool $isActive,
        public readonly ?\DateTime $createdAt = null,
        public readonly ?\DateTime $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            slug: $data['slug'] ?? '',
            content: $data['content'] ?? '',
            isActive: (bool)($data['is_active'] ?? true),
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'content' => $this->content,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

