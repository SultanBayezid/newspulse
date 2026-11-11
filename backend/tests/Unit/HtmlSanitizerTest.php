<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer;
    }

    public function test_script_contents_are_removed_entirely(): void
    {
        $this->assertSame(
            '<p>Safe</p>',
            $this->sanitizer->clean('<p>Safe</p><script>alert(1)</script>')
        );
    }

    public function test_event_handler_attributes_are_stripped(): void
    {
        $clean = $this->sanitizer->clean('<p onclick="evil()" class="lead">Hello</p>');

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('Hello', $clean);
        $this->assertStringContainsString('class="lead"', $clean);
    }

    public function test_javascript_urls_are_blocked(): void
    {
        $clean = $this->sanitizer->clean('<a href="javascript:alert(1)">click</a>');

        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_data_uris_in_images_are_blocked(): void
    {
        $clean = $this->sanitizer->clean('<img src="data:text/html;base64,PHNjcmlwdD4=" alt="x">');

        $this->assertStringNotContainsString('src="data:', $clean);
    }

    public function test_safe_links_get_rel_attributes(): void
    {
        $clean = $this->sanitizer->clean('<a href="https://example.com">docs</a>');

        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('https://example.com', $clean);
    }

    public function test_iframes_are_dropped_with_their_contents(): void
    {
        $this->assertSame(
            '',
            $this->sanitizer->clean('<iframe src="https://evil.example"><p>inner</p></iframe>')
        );
    }

    public function test_valid_editorial_html_survives(): void
    {
        $html = '<h2>Headline</h2><p>Paragraph with <strong>bold</strong> and <em>italic</em>.</p>'
            . '<ul><li>Item one</li><li>Item two</li></ul>'
            . '<blockquote><p>Quote</p></blockquote>'
            . '<img src="https://cdn.example.com/photo.jpg" alt="A photo">';

        $clean = $this->sanitizer->clean($html);

        $this->assertStringContainsString('<h2>', $clean);
        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<ul>', $clean);
        $this->assertStringContainsString('<blockquote>', $clean);
        $this->assertStringContainsString('alt="A photo"', $clean);
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', $this->sanitizer->clean(null));
        $this->assertSame('', $this->sanitizer->clean(''));
    }
}
