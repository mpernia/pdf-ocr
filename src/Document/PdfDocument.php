<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Document;

use Mpernia\PdfOcr\Contracts\PdfDocumentInterface;
use Mpernia\PdfOcr\Contracts\PdfPageInterface;
use Traversable;

final readonly class PdfDocument implements PdfDocumentInterface
{
    /** @var array<int, PdfPageInterface> */
    private array $pages;

    /**
     * @param array<int, PdfPageInterface> $pages Pages keyed by 1-based page number.
     */
    public function __construct(
        private string $path,
        private string $name,
        private int $size,
        array $pages,
    ) {
        if ($size < 0) {
            throw new \InvalidArgumentException('File size cannot be negative.');
        }

        foreach ($pages as $pageNumber => $page) {
            if ($pageNumber !== $page->getNumber()) {
                throw new \InvalidArgumentException('Pages must be keyed by their 1-based page number.');
            }
        }

        $this->pages = $pages;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return int<0, max>
     */
    public function getPageCount(): int
    {
        return count($this->pages);
    }

    public function getPages(): iterable
    {
        return $this->pages;
    }

    public function getPage(int $pageNumber): PdfPageInterface
    {
        if (!isset($this->pages[$pageNumber])) {
            throw new \OutOfBoundsException(sprintf('Page %d does not exist in this PDF.', $pageNumber));
        }

        return $this->pages[$pageNumber];
    }

    public function getIterator(): Traversable
    {
        yield from $this->pages;
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->getPageCount();
    }
}
