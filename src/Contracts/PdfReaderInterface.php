<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

use Mpernia\PdfOcr\Contracts\PdfDocumentInterface;

interface PdfReaderInterface
{
    public function open(string $filePath): PdfDocumentInterface;
}
