<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

interface PageTextRecognizerInterface
{
    /** Recognize text in a rendered page image. */
    public function recognize(string $imagePath, ?string $language = null): string;
}
