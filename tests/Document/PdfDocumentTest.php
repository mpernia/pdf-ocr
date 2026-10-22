<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Document;

use Mpernia\PdfOcr\Document\PdfDocument;
use Mpernia\PdfOcr\Document\PdfPage;
use PHPUnit\Framework\TestCase;

final class PdfDocumentTest extends TestCase
{
    public function testItProvidesGenericMetadataAndPageAccess(): void
    {
        $firstPage = new PdfPage(1, 'First page');
        $secondPage = new PdfPage(2, 'Second page');

        $document = new PdfDocument(
            '/tmp/example.pdf',
            'example.pdf',
            1024,
            [1 => $firstPage, 2 => $secondPage],
        );

        self::assertSame('example.pdf', $document->getName());
        self::assertSame(1024, $document->getSize());
        self::assertSame(2, $document->getPageCount());
        self::assertSame('Second page', $document->getPage(2)->getText());
        self::assertCount(2, $document);
        self::assertSame([1, 2], array_keys(iterator_to_array($document)));
    }

    public function testItThrowsForMissingPage(): void
    {
        $document = new PdfDocument('/tmp/example.pdf', 'example.pdf', 10, []);

        $this->expectException(\OutOfBoundsException::class);
        $document->getPage(1);
    }
}
