<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

/**
 * Renders a single PDF page into an image and returns the image file path.
 * The caller is responsible for deleting the generated image.
 */
interface PdfPageRendererInterface
{
    public function renderPage(string $pdfPath, int $pageNumber, int $dpi = 200): string;
}
