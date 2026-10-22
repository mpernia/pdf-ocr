<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Renderer;

use InvalidArgumentException;
use Mpernia\PdfOcr\Renderer\PopplerPdfPageRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Argument validation only: rendering needs the real `pdftoppm` binary, so the
 * process itself is not exercised here.
 */
final class PopplerPdfPageRendererTest extends TestCase
{
    public function testItRejectsAMissingPdf(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PDF file is missing or unreadable');

        (new PopplerPdfPageRenderer())->renderPage(__DIR__ . '/../Fixtures/does-not-exist.pdf', 1);
    }

    public function testItRejectsAPageNumberBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page number must be greater than zero');

        (new PopplerPdfPageRenderer())->renderPage(__DIR__ . '/../Fixtures/invoice.pdf', 0);
    }

    public function testItRejectsADpiOutsideTheSupportedRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DPI must be between 72 and 600');

        (new PopplerPdfPageRenderer())->renderPage(__DIR__ . '/../Fixtures/invoice.pdf', 1, 1000);
    }
}
