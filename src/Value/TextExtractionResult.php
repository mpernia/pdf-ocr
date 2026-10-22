<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Value;

final readonly class TextExtractionResult
{
    public function __construct(
        public string $text,
        public string $method,
        public ?float $confidence = null,
    ) {
        if ($this->confidence !== null && ($this->confidence < 0 || $this->confidence > 100)) {
            throw new \InvalidArgumentException('Confidence must be between 0 and 100.');
        }
    }
}
