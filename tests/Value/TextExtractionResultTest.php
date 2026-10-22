<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Value;

use InvalidArgumentException;
use Mpernia\PdfOcr\Value\TextExtractionResult;
use PHPUnit\Framework\TestCase;

final class TextExtractionResultTest extends TestCase
{
    public function testItCarriesTheTextTheMethodAndTheConfidence(): void
    {
        $result = new TextExtractionResult('page text', 'ocr', 87.5);

        self::assertSame('page text', $result->text);
        self::assertSame('ocr', $result->method);
        self::assertSame(87.5, $result->confidence);
    }

    public function testConfidenceIsOptional(): void
    {
        self::assertNull((new TextExtractionResult('page text', 'native'))->confidence);
    }

    public function testItRejectsNegativeConfidence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Confidence must be between 0 and 100.');

        new TextExtractionResult('page text', 'ocr', -0.1);
    }

    public function testItRejectsConfidenceAboveOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TextExtractionResult('page text', 'ocr', 100.1);
    }
}
