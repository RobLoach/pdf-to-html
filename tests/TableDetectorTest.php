<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

use Ahmaadkhader\PdfToHtml\TableDetector;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TableDetector.
 */
class TableDetectorTest extends TestCase {

  protected TableDetector $detector;

  protected function setUp(): void {
    $this->detector = new TableDetector();
  }

  /**
   * Build a DataTm entry at the given position.
   */
  protected function seg(string $text, float $x, float $y): array {
    return [[1, 0, 0, 1, $x, $y], $text, 'F1', 12];
  }

  public function testDetectsTableFromLargeColumnGaps(): void {
    $data_tm = [
      $this->seg('Name', 72, 700),
      $this->seg('Age', 300, 700),
      $this->seg('Alice', 72, 680),
      $this->seg('42', 300, 680),
      $this->seg('A plain paragraph line', 72, 660),
    ];

    $regions = $this->detector->detectTableRegions($data_tm);

    $this->assertCount(1, $regions);
    $this->assertSame(2, $regions[0]['numCols']);
    $this->assertSame([700.0, 680.0], $regions[0]['yValues']);
    $this->assertEqualsWithDelta(72, $regions[0]['colStarts'][0], 1);
    $this->assertEqualsWithDelta(300, $regions[0]['colStarts'][1], 1);
  }

  public function testBulletLinesAreNotTables(): void {
    $data_tm = [
      $this->seg('● First bullet', 72, 700),
      $this->seg('indented text', 250, 700),
      $this->seg('● Second bullet', 72, 680),
      $this->seg('more text', 250, 680),
    ];

    $this->assertSame([], $this->detector->detectTableRegions($data_tm));
  }

  public function testSingleRowIsNotATable(): void {
    $data_tm = [
      $this->seg('Left', 72, 700),
      $this->seg('Right', 300, 700),
      $this->seg('A plain paragraph line', 72, 680),
    ];

    $this->assertSame([], $this->detector->detectTableRegions($data_tm));
  }

  public function testSecondaryDetectionFindsNarrowColumns(): void {
    $data_tm = [
      $this->seg('Col1', 72, 700),
      $this->seg('Col2', 140, 700),
      $this->seg('Col3', 210, 700),
      $this->seg('a', 72, 680),
      $this->seg('b', 140, 680),
      $this->seg('c', 210, 680),
      $this->seg('d', 72, 660),
      $this->seg('e', 140, 660),
      $this->seg('f', 210, 660),
    ];

    $regions = $this->detector->detectTableRegions($data_tm);

    $this->assertCount(1, $regions);
    $this->assertSame(3, $regions[0]['numCols']);
    $this->assertCount(3, $regions[0]['yValues']);
  }

  public function testFindTableForLine(): void {
    $regions = [
      ['tableId' => 0, 'yValues' => [700.0, 680.0], 'numCols' => 2, 'colStarts' => [72, 300]],
    ];

    $this->assertNotNull($this->detector->findTableForLine(700, $regions));
    $this->assertNotNull($this->detector->findTableForLine(681, $regions));
    $this->assertNull($this->detector->findTableForLine(500, $regions));
  }

  public function testBuildTableCellTextsAssignsSegmentsToColumns(): void {
    $segments = [
      ['x' => 72.0, 'text' => 'John'],
      ['x' => 100.0, 'text' => ' ', 'isSpace' => TRUE],
      ['x' => 110.0, 'text' => 'Doe'],
      ['x' => 300.0, 'text' => '42'],
    ];

    $cells = $this->detector->buildTableCellTexts($segments, [72, 300], 2);

    $this->assertSame(['John Doe', '42'], $cells);
  }

  public function testBuildTableCellTextsFillsEmptyColumns(): void {
    $segments = [
      ['x' => 300.0, 'text' => 'only right'],
    ];

    $cells = $this->detector->buildTableCellTexts($segments, [72, 300], 2);

    $this->assertSame(['', 'only right'], $cells);
  }

}
