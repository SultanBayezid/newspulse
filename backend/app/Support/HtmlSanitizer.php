<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;

/**
 * Whitelist-based HTML sanitizer for user submitted article and page bodies.
 * Removes every tag/attribute that is not explicitly allowed and blocks
 * javascript:/data: URLs inside links and images.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'blockquote', 'ul', 'ol', 'li', 'a', 'img',
        'figure', 'figcaption', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'span', 'div',
    ];

    /**
     * Tags whose entire subtree is removed, not just the tag itself.
     */
    private const DROP_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form',
        'input', 'button', 'textarea', 'select', 'link', 'meta', 'base', 'title',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'pre' => ['class'],
        'code' => ['class'],
        '*' => ['class'],
    ];

    public function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $doc = new DOMDocument();

        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><body>' . $html . '</body>',
            LIBXML_NOWARNING | LIBXML_NOERROR
        );
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        $this->walk($body);

        $inner = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $inner .= $doc->saveHTML($child);
        }

        return trim($inner);
    }

    private function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            $this->walk($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $this->filterAttributes($child, $tag);

            if ($tag === 'a') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    private function filterAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(
            self::ALLOWED_ATTRIBUTES['*'],
            self::ALLOWED_ATTRIBUTES[$tag] ?? []
        );

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true)) {
                $value = trim($attribute->value);

                if (preg_match('/^\s*(javascript|data|vbscript)\s*:/i', $value)) {
                    $element->removeAttribute($attribute->name);
                }
            }
        }
    }
}
