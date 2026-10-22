<?php

declare(strict_types=1);

namespace Mpernia\PdfOcr\Tests\Ocr;

use InvalidArgumentException;
use Mpernia\PdfOcr\Ocr\TesseractCliRecognizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Drives the adapter with a fake `tesseract` script, so the suite does not
 * require the real binary to be installed.
 */
final class TesseractCliRecognizerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/pdf-ocr-test-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0o777, true);
        file_put_contents($this->directory . '/page.png', 'not really an image');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function testItReturnsTheTrimmedOutputOfTheBinary(): void
    {
        $recognizer = new TesseractCliRecognizer($this->binary("echo '  texto reconocido  '"));

        self::assertSame('texto reconocido', $recognizer->recognize($this->image()));
    }

    public function testItPassesTheLanguageAsItsOwnArgument(): void
    {
        $recognizer = new TesseractCliRecognizer($this->binary('echo "$*"'));

        $output = $recognizer->recognize($this->image(), 'spa');

        self::assertStringEndsWith('stdout -l spa', $output);
    }

    public function testItRejectsAMissingImage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OCR image is missing or unreadable');

        (new TesseractCliRecognizer($this->binary('echo x')))->recognize($this->directory . '/missing.png');
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

    private function binary(string $body): string
    {
        $path = $this->directory . '/tesseract';
        file_put_contents($path, "#!/bin/sh\n" . $body . "\n");
        chmod($path, 0o755);

        return $path;
    }
}
