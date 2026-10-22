<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Ocr;

use InvalidArgumentException;
use Mpernia\PdfOcr\Ocr\TesseractCliRecognizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Drives the adapter with a fake `tesseract` script that prints TSV, so the
 * suite does not require the real binary.
 */
final class TesseractCliRecognizerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/pdf-ocr-test-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0o777, true);
        file_put_contents($this->directory . '/page.png', 'not really an image');
        file_put_contents($this->directory . '/output.tsv', $this->tsvWithWords());
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function testItReturnsTheRecognisedTextAndItsConfidence(): void
    {
        $result = (new TesseractCliRecognizer($this->printingOutput()))->recognize($this->image());

        self::assertSame("hola mundo\nsegunda línea", $result->text);
        self::assertSame('ocr', $result->method);
        self::assertSame(75.0, $result->confidence);
    }

    public function testItReturnsNoConfidenceWhenNothingWasRead(): void
    {
        file_put_contents($this->directory . '/output.tsv', implode("\n", [
            "level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext",
            "1\t1\t0\t0\t0\t0\t0\t0\t0\t0\t-1\t",
        ]) . "\n");

        $result = (new TesseractCliRecognizer($this->printingOutput()))->recognize($this->image());

        self::assertSame('', $result->text);
        self::assertNull($result->confidence);
    }

    public function testItAsksForTsvAndPassesTheLanguageAsItsOwnArgument(): void
    {
        $argsFile = $this->directory . '/args.txt';
        $script = sprintf(
            "echo \"\$*\" > %s\ncat %s",
            escapeshellarg($argsFile),
            escapeshellarg($this->directory . '/output.tsv'),
        );

        (new TesseractCliRecognizer($this->binary($script)))->recognize($this->image(), 'spa');

        self::assertStringEndsWith('stdout -l spa tsv', trim((string) file_get_contents($argsFile)));
    }

    public function testItRejectsAMissingImage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR image is missing or unreadable');

        (new TesseractCliRecognizer($this->printingOutput()))->recognize($this->directory . '/missing.png');
    }

    public function testItFailsWhenTheBinaryFails(): void
    {
        $recognizer = new TesseractCliRecognizer($this->binary("echo 'boom' >&2\nexit 3"));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exit code 3');

        $recognizer->recognize($this->image());
    }

    private function image(): string
    {
        return $this->directory . '/page.png';
    }

    private function printingOutput(): string
    {
        return $this->binary('cat ' . escapeshellarg($this->directory . '/output.tsv'));
    }

    private function tsvWithWords(): string
    {
        return implode("\n", [
            "level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext",
            "1\t1\t0\t0\t0\t0\t0\t0\t1500\t2000\t-1\t",
            "4\t1\t1\t1\t0\t0\t40\t60\t600\t40\t-1\t",
            "5\t1\t1\t1\t1\t1\t40\t60\t200\t40\t90\thola",
            "5\t1\t1\t1\t1\t2\t260\t60\t300\t40\t80\tmundo",
            "4\t1\t1\t1\t0\t0\t40\t140\t600\t40\t-1\t",
            "5\t1\t1\t1\t2\t1\t40\t140\t400\t40\t70\tsegunda",
            "5\t1\t1\t1\t2\t2\t460\t140\t300\t40\t60\tlínea",
        ]) . "\n";
    }

    private function binary(string $body): string
    {
        $path = $this->directory . '/tesseract';
        file_put_contents($path, "#!/bin/sh\n" . $body . "\n");
        chmod($path, 0o755);

        return $path;
    }
}
