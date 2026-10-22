# PDF OCR

A small, extensible PHP package for reading PDF files as structured documents. It provides generic PDF metadata and page-level access without knowing the business meaning or schema of the document's contents.

> **Scope:** the default reader extracts embedded/selectable text from PDFs using `smalot/pdfparser`. Scanned pages have no such text; for those, wire an OCR adapter (`TesseractCliRecognizer`) and a page renderer (`PopplerPdfPageRenderer`). The package never decides for you which engine runs: you inject it.

## Requirements

- PHP 8.2+
- Composer
- Optional, only if you use the bundled adapters: `tesseract` and/or `pdftoppm` available on `PATH`

## Install

```bash
composer require mpernia/pdf-ocr
```

## Quick start

```php
use Mpernia\PdfOcr\Reader\PdfParserReader;

$reader = new PdfParserReader();
$document = $reader->open('/path/to/document.pdf');

echo $document->getName();
echo $document->getSize();
echo $document->getPageCount();

foreach ($document->getPages() as $page) {
    printf("Page %d: %s\n", $page->getNumber(), $page->getText());
}

$firstPage = $document->getPage(1); // Page numbers are 1-based.
```

## Design

- `PdfReaderInterface`: opens a PDF and returns a generic document.
- `PdfDocumentInterface`: generic file metadata and page access.
- `PdfPageInterface`: generic page number, text, and metadata.
- `PageTextRecognizerInterface`: extension point for OCR engines.
- `PageTextExtractorInterface`: decides the text of a page from its native text and, optionally, a recognizer.
- `PageTextRendererInterface`: turns a PDF page into an image, the input an OCR engine needs.
- `PdfParserReader`: default adapter for text already embedded in the PDF.

The package deliberately does **not** parse invoices, embargo notices, contracts, or any other domain-specific document. Consumers own that layer, for example:

```php
final class InvoicePageParser
{
    public function parse(PdfPageInterface $page): InvoicePageData
    {
        // Domain-specific interpretation belongs here.
    }
}
```

## OCR and rendering adapters

Both adapters are opt-in and small wrappers around a CLI tool. Nothing runs unless you construct them, and the arguments are passed as an array to `proc_open`, never through a shell.

```php
use Mpernia\PdfOcr\Extraction\NativeTextExtractor;
use Mpernia\PdfOcr\Extraction\OcrFallbackTextExtractor;
use Mpernia\PdfOcr\Ocr\TesseractCliRecognizer;
use Mpernia\PdfOcr\Reader\PdfParserReader;
use Mpernia\PdfOcr\Renderer\PopplerPdfPageRenderer;

// Native text when the page has it; OCR only when it does not.
$reader = new PdfParserReader(
    textExtractor: new OcrFallbackTextExtractor(
        nativeExtractor: new NativeTextExtractor(),
        renderer: new PopplerPdfPageRenderer(),
        recognizer: new TesseractCliRecognizer(),
        language: 'spa',
    ),
);
```

- `TesseractCliRecognizer(string $binary = 'tesseract')` runs `tesseract <image> stdout [-l <lang>]`.
- `PopplerPdfPageRenderer(string $binary = 'pdftoppm', ?string $temporaryDirectory = null)` renders one page to a PNG (`dpi` 72–600) and returns its path, which the caller owns.

## Error handling

- `InvalidArgumentException`: file does not exist or is not readable, or an adapter argument is out of range.
- `RuntimeException`: the PDF cannot be parsed, the page cannot be accessed, or an external binary fails.
- `OutOfBoundsException`: requested page number is outside the document.

## Development

```bash
composer install
composer test   # PHPUnit
composer stan   # PHPStan level 8
```

The test suite includes a small PDF fixture, so the reader is exercised against a real file and not only against mocks.

## License

MIT
