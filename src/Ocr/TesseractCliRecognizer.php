<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Ocr;

/**
 * Uses the Tesseract CLI. Requires `tesseract` and the requested language data.
 */
final readonly class TesseractCliRecognizer implements \Mpernia\PdfOcr\Contracts\PageTextRecognizerInterface
{
    public function __construct(private string $binary = 'tesseract')
    {
    }

    public function recognize(string $imagePath, ?string $language = null): string
    {
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            throw new \InvalidArgumentException('OCR image is missing or unreadable.');
        }

        $command = [$this->binary, $imagePath, 'stdout'];
        if ($language !== null && trim($language) !== '') {
            // Tesseract language identifiers are passed as one argument, not shell-interpolated.
            $command[] = '-l';
            $command[] = $language;
        }

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
            throw new \RuntimeException('Unable to start Tesseract.');
        }

        fclose($pipes[0]);
        $text = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf('Tesseract failed (exit code %d): %s', $exitCode, trim($stderr)));
        }

        return trim($text);
    }
}
