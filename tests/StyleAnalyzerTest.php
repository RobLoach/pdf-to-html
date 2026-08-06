<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

use Ahmaadkhader\PdfToHtml\StyleAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for StyleAnalyzer.
 */
class StyleAnalyzerTest extends TestCase {

  protected StyleAnalyzer $analyzer;

  protected function setUp(): void {
    $this->analyzer = new StyleAnalyzer();
  }

  public function testDetectBodyFontSizeReturnsMostCommonByCharCount(): void {
    $lines = [
      ['text' => 'Short Title', 'fontSize' => 24.0],
      ['text' => 'A much longer body paragraph with many characters.', 'fontSize' => 12.0],
      ['text' => 'Another long body paragraph with many characters.', 'fontSize' => 12.0],
      NULL,
    ];
    $this->assertSame(12.0, $this->analyzer->detectBodyFontSize($lines));
  }

  public function testDetectBodyFontSizeDefaultsWithoutData(): void {
    $this->assertSame(12.0, $this->analyzer->detectBodyFontSize([]));
    $this->assertSame(12.0, $this->analyzer->detectBodyFontSize([NULL, ['text' => 'x']]));
  }

  public function testDetectBodyFontSizeCountsTableRowCells(): void {
    $lines = [
      ['text' => 'Tiny', 'fontSize' => 24.0],
      [
        'type' => 'table_row',
        'fontSize' => 10.0,
        'cells' => ['A very long table cell text', 'Another very long cell text'],
      ],
    ];
    $this->assertSame(10.0, $this->analyzer->detectBodyFontSize($lines));
  }

  public function testDetectBodyColorReturnsMostCommonColor(): void {
    $black = [0.0, 0.0, 0.0];
    $red = [1.0, 0.0, 0.0];
    $lines = [
      ['text' => 'A long black body paragraph of text', 'color' => $black],
      ['text' => 'Red', 'color' => $red],
      ['text' => 'No color line'],
      NULL,
    ];
    $this->assertSame($black, $this->analyzer->detectBodyColor($lines));
  }

  public function testDetectBodyColorReturnsNullWithoutColors(): void {
    $this->assertNull($this->analyzer->detectBodyColor([['text' => 'abc']]));
  }

  public function testDetectBodyColorFromSegments(): void {
    $segments = [
      ['color' => [0.0, 0.0, 0.0]],
      ['color' => [0.0, 0.0, 0.0]],
      ['color' => [1.0, 0.0, 0.0]],
      ['color' => NULL],
    ];
    $this->assertSame([0.0, 0.0, 0.0], $this->analyzer->detectBodyColorFromSegments($segments));
    $this->assertNull($this->analyzer->detectBodyColorFromSegments([['color' => NULL]]));
  }

  public function testDetectLinkColorFindsMostCommonNonBodyColor(): void {
    $black = [0.0, 0.0, 0.0];
    $blue = [17 / 255, 85 / 255, 204 / 255];
    $segments = [
      ['color' => $black],
      ['color' => $black],
      ['color' => $black],
      ['color' => $blue],
      ['color' => $blue],
      ['color' => [1.0, 1.0, 1.0]],
    ];
    $this->assertSame('#1155cc', $this->analyzer->detectLinkColor($segments, $black));
  }

  public function testDetectLinkColorReturnsNullWhenOnlyBodyColors(): void {
    $black = [0.0, 0.0, 0.0];
    $segments = [
      ['color' => $black],
      ['color' => [1.0, 1.0, 1.0]],
      ['color' => NULL],
    ];
    $this->assertNull($this->analyzer->detectLinkColor($segments, $black));
  }

  public function testDetectHeadingLevelThresholds(): void {
    $this->assertSame(1, $this->analyzer->detectHeadingLevel(24.0, 12.0));
    $this->assertSame(2, $this->analyzer->detectHeadingLevel(17.0, 12.0));
    $this->assertSame(3, $this->analyzer->detectHeadingLevel(14.0, 12.0));
    $this->assertSame(0, $this->analyzer->detectHeadingLevel(12.0, 12.0));
    $this->assertSame(0, $this->analyzer->detectHeadingLevel(0.0, 12.0));
    $this->assertSame(0, $this->analyzer->detectHeadingLevel(24.0, 0.0));
  }

  public function testBuildInlineStylesCombinesSizeAndColor(): void {
    $styles = $this->analyzer->buildInlineStyles(
      14.0, 12.0, [1.0, 0.0, 0.0], [0.0, 0.0, 0.0]
    );
    $this->assertSame('font-size: 14pt; color: #ff0000', $styles);
  }

  public function testBuildInlineStylesSkipsHeadingFontSize(): void {
    $styles = $this->analyzer->buildInlineStyles(
      24.0, 12.0, NULL, NULL, TRUE
    );
    $this->assertSame('', $styles);
  }

  public function testBuildInlineStylesSkipsBodyColorAndWhite(): void {
    $black = [0.0, 0.0, 0.0];
    $this->assertSame('', $this->analyzer->buildInlineStyles(12.0, 12.0, $black, $black));
    $this->assertSame('', $this->analyzer->buildInlineStyles(12.0, 12.0, [1.0, 1.0, 1.0], $black));
  }

  public function testColorToHex(): void {
    $this->assertSame('#000000', $this->analyzer->colorToHex([0.0, 0.0, 0.0]));
    $this->assertSame('#ffffff', $this->analyzer->colorToHex([1.0, 1.0, 1.0]));
    $this->assertSame('#336699', $this->analyzer->colorToHex([0.2, 0.4, 0.6]));
  }

}
