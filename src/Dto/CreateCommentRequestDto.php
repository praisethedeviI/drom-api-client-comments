<?php

declare(strict_types=1);

namespace Praisethedevil\ApiClientTask\Dto;

class CreateCommentRequestDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $text,
    ) {}
}
