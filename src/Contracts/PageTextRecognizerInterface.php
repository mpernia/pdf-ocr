<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

use Mpernia\PdfOcr\Value\TextExtractionResult;

/**
 * Recognises the text of one page image with an OCR engine.
 */
interface PageTextRecognizerInterface
{
    /**
     * @param string|null $language engine-specific language identifier (for example "spa")
     *
     * @return TextExtractionResult recognised text, the method that produced it
     *                              and the engine confidence from 0 to 100, or
     *                              null when the engine cannot report one
     */
    public function recognize(string $imagePath, ?string $language = null): TextExtractionResult;
}
