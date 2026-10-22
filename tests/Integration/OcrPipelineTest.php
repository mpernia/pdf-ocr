<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Integration;

use Mpernia\PdfOcr\Extraction\NativeTextExtractor;
use Mpernia\PdfOcr\Extraction\OcrFallbackTextExtractor;
use Mpernia\PdfOcr\Ocr\TesseractCliRecognizer;
use Mpernia\PdfOcr\Renderer\PopplerPdfPageRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the OCR pipeline with the real binaries.
 *
 * Skipped when `pdftoppm` or `tesseract` are not installed, so the suite stays
 * runnable on a machine without them; CI installs both and runs it for real.
 */
final class OcrPipelineTest extends TestCase
{
    private string $pdf;

    protected function setUp(): void
    {
        $this->pdf = dirname(__DIR__) . '/Fixtures/invoice.pdf';

        self::requireBinary('pdftoppm');
        self::requireBinary('tesseract');
    }

    public function testPopplerRendersAPageAndTesseractReadsIt(): void
    {
        $image = (new PopplerPdfPageRenderer())->renderPage($this->pdf, 1, 150);

        try {
            self::assertFileExists($image);

            $text = (new TesseractCliRecognizer())->recognize($image);
        } finally {
            @unlink($image);
        }

        self::assertStringContainsStringIgnoringCase('factura', $text);
    }

    public function testTheFallbackExtractorRendersAndRecognisesWhenNativeTextIsShort(): void
    {
        $extractor = new OcrFallbackTextExtractor(
            nativeExtractor: new NativeTextExtractor(),
            renderer: new PopplerPdfPageRenderer(),
            recognizer: new TesseractCliRecognizer(),
            language: 'eng',
            dpi: 150,
            minimumNativeCharacters: 10000,
        );

        $result = $extractor->extract($this->pdf, 1, 'too short to count as text');

        self::assertSame('ocr', $result->method);
        self::assertStringContainsStringIgnoringCase('factura', $result->text);
    }

    private static function requireBinary(string $binary): void
    {
        exec(sprintf('command -v %s 2>/dev/null', $binary), $output, $exitCode);

        if ($exitCode !== 0) {
            self::markTestSkipped(sprintf('The %s binary is not installed.', $binary));
        }
    }
}
