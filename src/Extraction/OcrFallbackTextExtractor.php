<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Extraction;

use Mpernia\PdfOcr\Contracts\PageTextExtractorInterface;
use Mpernia\PdfOcr\Contracts\PageTextRecognizerInterface;
use Mpernia\PdfOcr\Contracts\PdfPageRendererInterface;
use Mpernia\PdfOcr\Value\TextExtractionResult;
use Throwable;

final readonly class OcrFallbackTextExtractor implements PageTextExtractorInterface
{
    public function __construct(
        private PageTextExtractorInterface $nativeExtractor,
        private PdfPageRendererInterface $renderer,
        private PageTextRecognizerInterface $recognizer,
        private string $language = 'eng',
        private int $dpi = 200,
        private int $minimumNativeCharacters = 10,
    ) {
        if ($dpi < 72 || $dpi > 600) {
            throw new \InvalidArgumentException('DPI must be between 72 and 600.');
        }

        if ($minimumNativeCharacters < 0) {
            throw new \InvalidArgumentException('Minimum native character count cannot be negative.');
        }
    }

    public function extract(string $pdfPath, int $pageNumber, string $nativeText): TextExtractionResult
    {
        $nativeResult = $this->nativeExtractor->extract($pdfPath, $pageNumber, $nativeText);

        if (mb_strlen(trim($nativeResult->text)) >= $this->minimumNativeCharacters) {
            return $nativeResult;
        }

        $imagePath = $this->renderer->renderPage($pdfPath, $pageNumber, $this->dpi);

        try {
            $recognizedText = $this->recognizer->recognize($imagePath, $this->language);

            return new TextExtractionResult($recognizedText, 'ocr');
        } finally {
            if (is_file($imagePath)) {
                @unlink($imagePath);
            }
        }
    }
}
