<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

interface PdfPageInterface
{
    public function getNumber(): int;

    /** Text embedded in the PDF or text returned by an OCR adapter. */
    public function getText(): string;

    public function getWidth(): ?float;

    public function getHeight(): ?float;

    /** @return array<string, scalar|null> */
    public function getMetadata(): array;
}
