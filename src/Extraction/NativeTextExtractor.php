<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Extraction;

use Mpernia\PdfOcr\Contracts\PageTextExtractorInterface;
use Mpernia\PdfOcr\Value\TextExtractionResult;

final class NativeTextExtractor implements PageTextExtractorInterface
{
    public function extract(string $pdfPath, int $pageNumber, string $nativeText): TextExtractionResult
    {
        return new TextExtractionResult($nativeText, 'native');
    }
}
