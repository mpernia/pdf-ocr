<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Extraction;

use Mpernia\PdfOcr\Contracts\PageTextRecognizerInterface;
use Mpernia\PdfOcr\Contracts\PdfPageRendererInterface;
use Mpernia\PdfOcr\Contracts\PageTextExtractorInterface;
use Mpernia\PdfOcr\Extraction\NativeTextExtractor;
use Mpernia\PdfOcr\Extraction\OcrFallbackTextExtractor;
use Mpernia\PdfOcr\Value\TextExtractionResult;
use PHPUnit\Framework\TestCase;

final class OcrFallbackTextExtractorTest extends TestCase
{
    public function testItKeepsNativeTextWhenEnoughTextExists(): void
    {
        $renderer = new class implements PdfPageRendererInterface {
            public function renderPage(string $pdfPath, int $pageNumber, int $dpi = 200): string
            {
                throw new \LogicException('Renderer should not be called.');
            }
        };
        $recognizer = new class implements PageTextRecognizerInterface {
            public function recognize(string $imagePath, ?string $language = null): TextExtractionResult
            {
                throw new \LogicException('Recognizer should not be called.');
            }
        };

        $extractor = new OcrFallbackTextExtractor(
            new NativeTextExtractor(),
            $renderer,
            $recognizer,
            minimumNativeCharacters: 3,
        );

        $result = $extractor->extract('/tmp/file.pdf', 1, 'Native text');

        self::assertSame('Native text', $result->text);
        self::assertSame('native', $result->method);
    }

    public function testItUsesOcrWhenNativeTextIsTooShort(): void
    {
        $imagePath = tempnam(sys_get_temp_dir(), 'pdfocr_test_');
        self::assertNotFalse($imagePath);
        file_put_contents($imagePath, 'fake image');

        $renderer = new class($imagePath) implements PdfPageRendererInterface {
            public function __construct(private string $imagePath) {}
            public function renderPage(string $pdfPath, int $pageNumber, int $dpi = 200): string
            {
                return $this->imagePath;
            }
        };
        $recognizer = new class implements PageTextRecognizerInterface {
            public function recognize(string $imagePath, ?string $language = null): TextExtractionResult
            {
                return new TextExtractionResult('Recognized by OCR', 'ocr', 91.5);
            }
        };

        $extractor = new OcrFallbackTextExtractor(
            new NativeTextExtractor(),
            $renderer,
            $recognizer,
            minimumNativeCharacters: 5,
        );

        $result = $extractor->extract('/tmp/file.pdf', 1, '');

        self::assertSame('Recognized by OCR', $result->text);
        self::assertSame('ocr', $result->method);
        self::assertSame(91.5, $result->confidence);
        self::assertFileDoesNotExist($imagePath);
    }
}
