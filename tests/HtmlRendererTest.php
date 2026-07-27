<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

use Ahmaadkhader\PdfToHtml\HtmlRenderer;
use Ahmaadkhader\PdfToHtml\StyleAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HtmlRenderer.
 */
class HtmlRendererTest extends TestCase {

  protected HtmlRenderer $renderer;

  protected function setUp(): void {
    $this->renderer = new HtmlRenderer(new StyleAnalyzer());
  }

  /**
   * Build a plain body line record.
   */
  protected function line(string $text, float $size = 12.0, array $extra = []): array {
    return $extra + [
      'text' => $text,
      'fontSize' => $size,
      'isBold' => FALSE,
      'isItalic' => FALSE,
      'color' => NULL,
      'linkSpans' => [],
    ];
  }

  public function testRendersParagraphs(): void {
    $html = $this->renderer->buildHtml([
      $this->line('First line of body text.'),
      NULL,
      $this->line('Second page text.'),
    ], 12.0);

    $this->assertSame(
      "<p>First line of body text.</p>\n<p>Second page text.</p>",
      $html
    );
  }

  public function testMergesConsecutiveBodyLinesIntoOneParagraph(): void {
    $html = $this->renderer->buildHtml([
      $this->line('First line.'),
      $this->line('Second line.'),
    ], 12.0);

    $this->assertSame('<p>First line.<br>Second line.</p>', $html);
  }

  public function testRendersClassBasedHeadingByDefault(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Document Title', 24.0),
      $this->line('Body text follows the title here.'),
    ], 12.0);

    $this->assertStringContainsString('<p class="h1">Document Title</p>', $html);
  }

  public function testRendersNativeHeadingsWithOption(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Document Title', 24.0),
      $this->line('Section Heading', 17.0),
    ], 12.0, NULL, ['native_headings' => TRUE]);

    $this->assertStringContainsString('<h1 class="h1">Document Title</h1>', $html);
    $this->assertStringContainsString('<h2 class="h2">Section Heading</h2>', $html);
  }

  public function testLongTextIsNotAHeading(): void {
    $long = 'This line is far too long to be treated as a heading because it '
      . 'exceeds the sixty-five character limit.';
    $html = $this->renderer->buildHtml([
      $this->line($long, 24.0),
    ], 12.0);

    $this->assertStringNotContainsString('class="h1"', $html);
  }

  public function testBoldShortLineBecomesSubHeading(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Development queues', 12.0, ['isBold' => TRUE]),
    ], 12.0);

    $this->assertStringContainsString('class="h3"', $html);
  }

  public function testRendersBulletList(): void {
    $html = $this->renderer->buildHtml([
      $this->line('● Apple'),
      $this->line('● Banana'),
    ], 12.0);

    $this->assertStringContainsString('<ul>', $html);
    $this->assertStringContainsString('<li>Apple</li>', $html);
    $this->assertStringContainsString('<li>Banana</li>', $html);
  }

  public function testRendersNumberedList(): void {
    $html = $this->renderer->buildHtml([
      $this->line('1. First step'),
      $this->line('2. Second step'),
    ], 12.0);

    $this->assertStringContainsString('<ol>', $html);
    $this->assertStringContainsString('<li>First step</li>', $html);
    $this->assertStringContainsString('<li>Second step</li>', $html);
  }

  public function testSkipsPageNumbersAndFootnotes(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Body text of the page.'),
      $this->line('3'),
      $this->line('Tiny footnote', 9.0),
    ], 12.0);

    $this->assertSame('<p>Body text of the page.</p>', $html);
  }

  public function testAutoLinksBareUrls(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Visit https://example.com today.'),
    ], 12.0);

    $this->assertStringContainsString(
      '<a href="https://example.com">https://example.com</a>',
      $html
    );
  }

  public function testAppliesAnnotationLinkSpans(): void {
    $html = $this->renderer->buildHtml([
      $this->line('See the Example site for details.', 12.0, [
        'linkSpans' => [['text' => 'Example', 'uri' => 'https://example.com']],
      ]),
    ], 12.0);

    $this->assertStringContainsString(
      '<a href="https://example.com">Example</a>',
      $html
    );
  }

  public function testEscapesHtmlSpecialCharacters(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Fish <&> chips cost $5.'),
    ], 12.0);

    $this->assertStringContainsString('Fish &lt;&amp;&gt; chips', $html);
  }

  public function testRendersTableWithHeaderAndBody(): void {
    $row = [
      'type' => 'table_row',
      'tableId' => 0,
      'fontSize' => 12.0,
      'isBold' => FALSE,
      'isItalic' => FALSE,
      'color' => NULL,
    ];
    $html = $this->renderer->buildHtml([
      ['cells' => ['Name', 'Age']] + $row,
      ['cells' => ['Alice', '42']] + $row,
    ], 12.0);

    $this->assertStringContainsString('<thead>', $html);
    $this->assertStringContainsString('<th>Name</th><th>Age</th>', $html);
    $this->assertStringContainsString('<tbody>', $html);
    $this->assertStringContainsString('<td>Alice</td><td>42</td>', $html);
  }

  public function testAppliesInlineStylesForDifferingColor(): void {
    $html = $this->renderer->buildHtml([
      $this->line('Normal black body text here.', 12.0, ['color' => [0.0, 0.0, 0.0]]),
      $this->line('A red warning appears here.', 12.0, ['color' => [1.0, 0.0, 0.0]]),
    ], 12.0, [0.0, 0.0, 0.0]);

    $this->assertStringContainsString('style="color: #ff0000"', $html);
    $this->assertStringNotContainsString('color: #000000', $html);
  }

}
