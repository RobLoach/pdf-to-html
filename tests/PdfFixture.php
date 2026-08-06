<?php

namespace Ahmaadkhader\PdfToHtml\Tests;

/**
 * Builds minimal single-page PDF files for integration tests.
 *
 * Generates a valid PDF 1.4 document with a correct xref table so that
 * smalot/pdfparser can parse it. Text is placed with explicit Tm text
 * matrices so font size and position are available through the DataTm
 * API used by the extraction pipeline.
 */
class PdfFixture {

  /**
   * Build a one-page PDF containing the given text lines.
   *
   * @param array $lines
   *   Each entry: [text, fontSize, x, y].
   *
   * @return string
   *   The raw PDF file contents.
   */
  public static function build(array $lines): string {
    $stream = '';
    foreach ($lines as [$text, $size, $x, $y]) {
      $escaped = strtr($text, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
      $stream .= sprintf(
        "BT /F1 %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET\n",
        $size, $x, $y, $escaped
      );
    }

    $objects = [
      '<< /Type /Catalog /Pages 2 0 R >>',
      '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
      '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792]'
      . ' /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
      '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
      "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . 'endstream',
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $i => $body) {
      $offsets[] = strlen($pdf);
      $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xref_pos = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 " . $count . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
      $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . $count . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref_pos . "\n%%EOF\n";

    return $pdf;
  }

  /**
   * Write a fixture PDF to a temporary file.
   *
   * @param array $lines
   *   Each entry: [text, fontSize, x, y].
   *
   * @return string
   *   Path to the temporary PDF file.
   */
  public static function writeTempPdf(array $lines): string {
    $path = tempnam(sys_get_temp_dir(), 'pdf2html_') . '.pdf';
    file_put_contents($path, self::build($lines));
    return $path;
  }

}
