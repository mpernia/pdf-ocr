<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Renderer;

use Mpernia\PdfOcr\Contracts\PdfPageRendererInterface;

/**
 * Renders a page using Poppler's pdftoppm executable.
 *
 * Requires `pdftoppm` to be installed and available on PATH.
 */
final readonly class PopplerPdfPageRenderer implements PdfPageRendererInterface
{
    public function __construct(
        private string $binary = 'pdftoppm',
        private ?string $temporaryDirectory = null,
    ) {
    }

    public function renderPage(string $pdfPath, int $pageNumber, int $dpi = 200): string
    {
        if (!is_file($pdfPath) || !is_readable($pdfPath)) {
            throw new \InvalidArgumentException('PDF file is missing or unreadable.');
        }

        if ($pageNumber < 1) {
            throw new \InvalidArgumentException('Page number must be greater than zero.');
        }

        if ($dpi < 72 || $dpi > 600) {
            throw new \InvalidArgumentException('DPI must be between 72 and 600.');
        }

        $directory = $this->temporaryDirectory ?? sys_get_temp_dir();
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('Temporary directory does not exist or is not writable.');
        }

        $outputPrefix = tempnam($directory, 'pdfocr_');
        if ($outputPrefix === false) {
            throw new \RuntimeException('Unable to create temporary output filename.');
        }
        @unlink($outputPrefix);

        $command = [
            $this->binary,
            '-f', (string) $pageNumber,
            '-l', (string) $pageNumber,
            '-r', (string) $dpi,
            '-singlefile',
            '-png',
            $pdfPath,
            $outputPrefix,
        ];

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start pdftoppm.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $imagePath = $outputPrefix . '.png';
        if ($exitCode !== 0 || !is_file($imagePath)) {
            @unlink($imagePath);
            throw new \RuntimeException(
                sprintf('PDF page rendering failed (exit code %d): %s %s', $exitCode, trim($stderr), trim($stdout)),
            );
        }

        return $imagePath;
    }
}
