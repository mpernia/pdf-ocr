<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Reader;

use Mpernia\PdfOcr\Contracts\PageTextExtractorInterface;
use Mpernia\PdfOcr\Contracts\PdfDocumentInterface;
use Mpernia\PdfOcr\Contracts\PdfReaderInterface;
use Mpernia\PdfOcr\Document\PdfDocument;
use Mpernia\PdfOcr\Document\PdfPage;
use Mpernia\PdfOcr\Extraction\NativeTextExtractor;
use Mpernia\PdfOcr\Value\TextExtractionResult;
use Smalot\PdfParser\Parser;
use Throwable;

final class PdfParserReader implements PdfReaderInterface
{
    public function __construct(
        private readonly ?Parser $parser = null,
        private readonly ?PageTextExtractorInterface $textExtractor = null,
    ) {
    }

    public function open(string $filePath): PdfDocumentInterface
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('PDF file is missing or unreadable: %s', $filePath));
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new \RuntimeException(sprintf('Unable to determine PDF file size: %s', $filePath));
        }

        try {
            $pdf = ($this->parser ?? new Parser())->parseFile($filePath);
            $pages = [];
            $pageNumber = 1;
            $extractor = $this->textExtractor ?? new NativeTextExtractor();

            foreach ($pdf->getPages() as $parsedPage) {
                $details = $parsedPage->getDetails();
                $nativeText = $parsedPage->getText();
                $result = $extractor->extract($filePath, $pageNumber, $nativeText);

                $pages[$pageNumber] = new PdfPage(
                    number: $pageNumber,
                    text: $result->text,
                    width: isset($details['MediaBox'][2]) ? (float) $details['MediaBox'][2] : null,
                    height: isset($details['MediaBox'][3]) ? (float) $details['MediaBox'][3] : null,
                    metadata: [
                        'rotation' => isset($details['Rotate']) ? (int) $details['Rotate'] : null,
                        'text_extraction_method' => $result->method,
                        'ocr_confidence' => $result->confidence,
                    ],
                );
                $pageNumber++;
            }

            return new PdfDocument(
                path: $filePath,
                name: basename($filePath),
                size: $size,
                pages: $pages,
            );
        } catch (Throwable $exception) {
            throw new \RuntimeException(
                sprintf('Unable to read PDF file: %s', $filePath),
                previous: $exception,
            );
        }
    }
}
