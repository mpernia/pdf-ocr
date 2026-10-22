<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Reader;

use InvalidArgumentException;
use Mpernia\PdfOcr\Reader\PdfParserReader;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class PdfParserReaderTest extends TestCase
{
    public function testItReadsARealPdfFile(): void
    {
        $path = __DIR__ . '/../Fixtures/invoice.pdf';

        $document = (new PdfParserReader())->open($path);

        self::assertSame('invoice.pdf', $document->getName());
        self::assertSame((int) filesize($path), $document->getSize());
        self::assertSame(1, $document->getPageCount());
        self::assertSame(1, $document->getPage(1)->getNumber());
        self::assertStringContainsString('FACTURA', $document->getPage(1)->getText());
        self::assertSame('native', $document->getPage(1)->getMetadata()['text_extraction_method'] ?? null);
    }

    public function testItRejectsAMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PDF file is missing or unreadable');

        (new PdfParserReader())->open(__DIR__ . '/../Fixtures/does-not-exist.pdf');
    }

    public function testItRejectsAPageOutsideTheDocument(): void
    {
        $document = (new PdfParserReader())->open(__DIR__ . '/../Fixtures/invoice.pdf');

        $this->expectException(OutOfBoundsException::class);

        $document->getPage(9);
    }
}
