<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

use Mpernia\PdfOcr\Value\TextExtractionResult;

/**
 * Extracts text for one page. Implementations may use native PDF text,
 * OCR, or a combination of techniques.
 */
interface PageTextExtractorInterface
{
    public function extract(string $pdfPath, int $pageNumber, string $nativeText): TextExtractionResult;
}
