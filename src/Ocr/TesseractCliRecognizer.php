<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Ocr;

use Mpernia\PdfOcr\Contracts\PageTextRecognizerInterface;
use Mpernia\PdfOcr\Value\TextExtractionResult;

/**
 * Uses the Tesseract CLI. Requires `tesseract` and the requested language data.
 *
 * The text is read from Tesseract's TSV output, which also carries a per-word
 * confidence; the result reports the average of the words actually recognised.
 */
final readonly class TesseractCliRecognizer implements PageTextRecognizerInterface
{
    public function __construct(private string $binary = 'tesseract')
    {
    }

    public function recognize(string $imagePath, ?string $language = null): TextExtractionResult
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

        // TSV carries the confidence per word; plain text output does not.
        $command[] = 'tsv';

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
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf('Tesseract failed (exit code %d): %s', $exitCode, trim($stderr)));
        }

        return self::parseTsv((string) $output);
    }

    private static function parseTsv(string $tsv): TextExtractionResult
    {
        $rows = preg_split('/\r?\n/', trim($tsv)) ?: [];
        $header = explode("\t", (string) array_shift($rows));
        $confidenceColumn = array_search('conf', $header, true);
        $textColumn = array_search('text', $header, true);

        if (!is_int($confidenceColumn) || !is_int($textColumn)) {
            return new TextExtractionResult('', 'ocr');
        }

        $lines = [];
        $currentLine = null;
        $words = [];
        $confidences = [];

        foreach ($rows as $row) {
            if ($row === '') {
                continue;
            }

            $columns = explode("\t", $row);
            $confidence = $columns[$confidenceColumn] ?? '';
            $word = trim($columns[$textColumn] ?? '');

            // Container rows (page, block, paragraph, line) carry -1 and no text.
            if (!is_numeric($confidence) || (float) $confidence < 0 || $word === '') {
                continue;
            }

            $lineKey = implode(':', array_slice($columns, 0, 5));

            if ($currentLine !== null && $lineKey !== $currentLine) {
                $lines[] = implode(' ', $words);
                $words = [];
            }

            $currentLine = $lineKey;
            $words[] = $word;
            $confidences[] = (float) $confidence;
        }

        if ($words !== []) {
            $lines[] = implode(' ', $words);
        }

        $confidence = $confidences === [] ? null : round(array_sum($confidences) / count($confidences), 1);

        return new TextExtractionResult(implode("\n", $lines), 'ocr', $confidence);
    }
}
