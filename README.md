# PDF to HTML

Standalone PHP library for extracting semantic HTML from PDF files using [smalot/pdfparser](https://github.com/smalot/pdfparser).

## Features

- **Heading detection** — identifies heading levels from font size ratios
- **List detection** — groups bullet and numbered items into `<ul>`/`<ol>` lists
- **Table detection** — identifies tabular content from X-coordinate column clustering
- **Link extraction** — matches PDF link annotations to text content
- **Inline styles** — preserves font size and color differences as CSS

## Installation

```bash
composer require ahmaadkhader/pdf-to-html
```

## Usage

```php
use Ahmaadkhader\PdfToHtml\PdfToHtml;
use Ahmaadkhader\PdfToHtml\StyleAnalyzer;
use Ahmaadkhader\PdfToHtml\TableDetector;
use Ahmaadkhader\PdfToHtml\LinkExtractor;
use Ahmaadkhader\PdfToHtml\HtmlRenderer;

$styleAnalyzer = new StyleAnalyzer();
$tableDetector = new TableDetector();
$linkExtractor = new LinkExtractor($styleAnalyzer);
$htmlRenderer = new HtmlRenderer($styleAnalyzer);

$converter = new PdfToHtml($styleAnalyzer, $tableDetector, $linkExtractor, $htmlRenderer);

// Extract plain text.
$text = $converter->extractText('/path/to/file.pdf');

// Extract semantic HTML with headings, lists, tables and links.
$html = $converter->extractHtml('/path/to/file.pdf');

// Use native heading tags (h1-h6) instead of class-based.
$html = $converter->extractHtml('/path/to/file.pdf', ['native_headings' => true]);
```

## Architecture

| Class | Responsibility |
|---|---|
| `PdfToHtml` | Core orchestration — parses PDF, coordinates sub-components |
| `StyleAnalyzer` | Font size, color, heading level, and inline style detection |
| `TableDetector` | Table region detection from DataTm positioning data |
| `LinkExtractor` | PDF link annotation extraction and text matching |
| `HtmlRenderer` | Renders classified content lines into semantic HTML |

## Requirements

- PHP 8.1+
- `smalot/pdfparser` ^2.12

## License

GPL-2.0-or-later
