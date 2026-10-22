<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Contracts;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Generic access to a PDF file. Page numbering is 1-based.
 *
 * @extends IteratorAggregate<int, PdfPageInterface>
 */
interface PdfDocumentInterface extends Countable, IteratorAggregate
{
    public function getPath(): string;

    public function getName(): string;

    public function getSize(): int;

    public function getPageCount(): int;

    /** @return iterable<int, PdfPageInterface> Pages keyed by their 1-based page number. */
    public function getPages(): iterable;

    public function getPage(int $pageNumber): PdfPageInterface;

    /** @return Traversable<int, PdfPageInterface> */
    public function getIterator(): Traversable;

    public function count(): int;
}
