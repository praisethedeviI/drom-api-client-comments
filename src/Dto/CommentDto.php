<?php

declare(strict_types=1);

namespace Praisethedevil\ApiClientTask\Dto;

class CommentDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $text,
    ) {}
}
