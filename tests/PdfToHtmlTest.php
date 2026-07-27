<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

use Ahmaadkhader\PdfToHtml\PdfToHtml;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the PdfToHtml conversion pipeline.
 */
class PdfToHtmlTest extends TestCase {

  protected PdfToHtml $converter;

  /**
   * Temporary fixture files to clean up.
   */
  protected array $tempFiles = [];

  protected function setUp(): void {
    $this->converter = new PdfToHtml();
  }

  protected function tearDown(): void {
    foreach ($this->tempFiles as $path) {
      @unlink($path);
    }
    $this->tempFiles = [];
  }

  /**
   * Build a fixture PDF and register it for cleanup.
   */
  protected function fixture(array $lines): string {
    $path = PdfFixture::writeTempPdf($lines);
    $this->tempFiles[] = $path;
    return $path;
  }

  public function testConstructorCreatesDefaultComponents(): void {
    $this->assertInstanceOf(PdfToHtml::class, new PdfToHtml());
  }

  public function testExtractTextReturnsEmptyForMissingFile(): void {
    $this->assertSame('', $this->converter->extractText('/nonexistent/file.pdf'));
  }

  public function testExtractHtmlReturnsEmptyForMissingFile(): void {
    $this->assertSame('', $this->converter->extractHtml('/nonexistent/file.pdf'));
  }

  public function testExtractTextReadsPdfContent(): void {
    $path = $this->fixture([
      ['Hello World', 12, 72, 720],
    ]);

    $this->assertStringContainsString('Hello World', $this->converter->extractText($path));
  }

  public function testExtractHtmlRendersParagraphs(): void {
    $path = $this->fixture([
      ['The quick brown fox jumps over the lazy dog.', 12, 72, 720],
    ]);

    $html = $this->converter->extractHtml($path);

    $this->assertStringContainsString('<p>', $html);
    $this->assertStringContainsString('The quick brown fox', $html);
  }

  public function testExtractHtmlDetectsHeadingFromFontSize(): void {
    $path = $this->fixture([
      ['Sample Title', 24, 72, 720],
      ['A longer body paragraph that establishes the body font size.', 12, 72, 680],
      ['Another body paragraph keeps twelve point as the body size.', 12, 72, 660],
    ]);

    $html = $this->converter->extractHtml($path);

    $this->assertStringContainsString('class="h1"', $html);
    $this->assertStringContainsString('Sample Title', $html);
  }

  public function testExtractHtmlNativeHeadingsOption(): void {
    $path = $this->fixture([
      ['Sample Title', 24, 72, 720],
      ['A longer body paragraph that establishes the body font size.', 12, 72, 680],
      ['Another body paragraph keeps twelve point as the body size.', 12, 72, 660],
    ]);

    $html = $this->converter->extractHtml($path, ['native_headings' => TRUE]);

    $this->assertStringContainsString('<h1 class="h1">', $html);
  }

  public function testExtractHtmlRendersBulletList(): void {
    $path = $this->fixture([
      ['A body paragraph that establishes the body font size.', 12, 72, 720],
      ['● Apple', 12, 72, 700],
      ['● Banana', 12, 72, 680],
    ]);

    $html = $this->converter->extractHtml($path);

    $this->assertStringContainsString('<ul>', $html);
    $this->assertStringContainsString('<li>Apple</li>', $html);
    $this->assertStringContainsString('<li>Banana</li>', $html);
  }

  public function testExtractHtmlEscapesMarkup(): void {
    $path = $this->fixture([
      ['Fish <&> chips for dinner tonight.', 12, 72, 720],
    ]);

    $html = $this->converter->extractHtml($path);

    $this->assertStringContainsString('&lt;&amp;&gt;', $html);
    $this->assertStringNotContainsString('<&>', $html);
  }

}
