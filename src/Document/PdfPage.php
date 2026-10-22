<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Document;

use Mpernia\PdfOcr\Contracts\PdfPageInterface;

final readonly class PdfPage implements PdfPageInterface
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(
        private int $number,
        private string $text,
        private ?float $width = null,
        private ?float $height = null,
        private array $metadata = [],
    ) {
        if ($number < 1) {
            throw new \InvalidArgumentException('Page number must be greater than zero.');
        }
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getWidth(): ?float
    {
        return $this->width;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
