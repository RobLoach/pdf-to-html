<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

use Ahmaadkhader\PdfToHtml\LinkExtractor;
use Ahmaadkhader\PdfToHtml\StyleAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LinkExtractor.
 */
class LinkExtractorTest extends TestCase {

  protected LinkExtractor $extractor;

  protected function setUp(): void {
    $this->extractor = new LinkExtractor(new StyleAnalyzer());
  }

  public function testLinkMatchScoreExactUrlDisplay(): void {
    $score = $this->extractor->linkMatchScore('example.com', 'https://example.com/');
    $this->assertSame(100.0, $score);
  }

  public function testLinkMatchScoreUrlPrefixDisplay(): void {
    $score = $this->extractor->linkMatchScore('example.com/docs/page', 'https://example.com/docs');
    $this->assertSame(95.0, $score);
  }

  public function testLinkMatchScorePathSegmentMatch(): void {
    $score = $this->extractor->linkMatchScore('readmore', 'https://foo.com/read-more');
    $this->assertGreaterThanOrEqual(80.0, $score);
  }

  public function testLinkMatchScoreNoMatch(): void {
    $this->assertSame(0.0, $this->extractor->linkMatchScore('hello', 'https://example.com/foo'));
    $this->assertSame(0.0, $this->extractor->linkMatchScore('', 'https://example.com'));
    $this->assertSame(0.0, $this->extractor->linkMatchScore('hello', ''));
  }

  public function testFindLinksForYposition(): void {
    $links = [
      ['uri' => 'https://a.example', 'xMin' => 0, 'xMax' => 100, 'yMin' => 695, 'yMax' => 705],
      ['uri' => 'https://b.example', 'xMin' => 0, 'xMax' => 100, 'yMin' => 500, 'yMax' => 510],
    ];

    $matches = $this->extractor->findLinksForYposition(700.0, $links);
    $this->assertCount(1, $matches);
    $this->assertSame('https://a.example', $matches[0]['uri']);

    $this->assertSame([], $this->extractor->findLinksForYposition(300.0, $links));
  }

  public function testMatchLinksToTextAssignsLinkSpans(): void {
    $blue = [17 / 255, 85 / 255, 204 / 255];
    $font_lines = [
      [
        'fontSize' => 12.0,
        'yPos' => 700,
        'columns' => [
          ['color' => $blue, 'text' => 'Example', 'x' => 100.0],
          ['color' => NULL, 'text' => ' plain text', 'x' => 150.0],
        ],
      ],
    ];
    $page_links = [
      ['uri' => 'https://example.com', 'xMin' => 90, 'xMax' => 160, 'yMin' => 695, 'yMax' => 705],
    ];

    $result = $this->extractor->matchLinksToText(
      $font_lines, $page_links, '#1155cc', [], []
    );

    $this->assertSame(
      [['text' => 'Example', 'uri' => 'https://example.com']],
      $result[0]['linkSpans']
    );
  }

  public function testMatchLinksToTextWithoutLinksAddsEmptySpans(): void {
    $font_lines = [
      ['fontSize' => 12.0, 'yPos' => 700, 'columns' => []],
    ];

    $result = $this->extractor->matchLinksToText($font_lines, [], NULL, [], []);

    $this->assertSame([], $result[0]['linkSpans']);
  }

}
