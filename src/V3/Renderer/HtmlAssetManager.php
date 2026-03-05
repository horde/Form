<?php
declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3\Renderer;

/**
 * HTML asset manager for managing JavaScript and CSS.
 *
 * Tracks scripts and stylesheets needed for form functionality.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class HtmlAssetManager implements AssetManager
{
    /**
     * JavaScript files.
     *
     * @var array<int, array{file: string, attrs: array<string, string>}>
     */
    protected array $scripts = [];

    /**
     * CSS stylesheets.
     *
     * @var array<int, array{file: string, attrs: array<string, string>}>
     */
    protected array $stylesheets = [];

    /**
     * Inline JavaScript code.
     *
     * @var array<string>
     */
    protected array $inlineScripts = [];

    /**
     * Inline CSS code.
     *
     * @var array<string>
     */
    protected array $inlineStyles = [];

    /**
     * Add a JavaScript file.
     */
    public function addScript(string $file, array $attrs = []): void
    {
        $this->scripts[] = ['file' => $file, 'attrs' => $attrs];
    }

    /**
     * Add a CSS stylesheet.
     */
    public function addStylesheet(string $file, array $attrs = []): void
    {
        $this->stylesheets[] = ['file' => $file, 'attrs' => $attrs];
    }

    /**
     * Add inline JavaScript code.
     */
    public function addInlineScript(string $code): void
    {
        $this->inlineScripts[] = $code;
    }

    /**
     * Add inline CSS code.
     */
    public function addInlineStyle(string $code): void
    {
        $this->inlineStyles[] = $code;
    }

    /**
     * Render all assets as HTML.
     */
    public function render(): string
    {
        $output = [];

        // Stylesheets
        foreach ($this->stylesheets as $css) {
            $attrs = array_merge(['rel' => 'stylesheet', 'href' => $css['file']], $css['attrs']);
            $output[] = $this->buildTag('link', $attrs);
        }

        // Inline styles
        if (!empty($this->inlineStyles)) {
            $output[] = '<style>';
            $output[] = implode("\n", $this->inlineStyles);
            $output[] = '</style>';
        }

        // Scripts
        foreach ($this->scripts as $js) {
            $attrs = array_merge(['src' => $js['file']], $js['attrs']);
            $output[] = $this->buildTag('script', $attrs, '');
        }

        // Inline scripts
        if (!empty($this->inlineScripts)) {
            $output[] = '<script>';
            $output[] = implode("\n", $this->inlineScripts);
            $output[] = '</script>';
        }

        return implode("\n", array_filter($output));
    }

    /**
     * Clear all assets.
     */
    public function clear(): void
    {
        $this->scripts = [];
        $this->stylesheets = [];
        $this->inlineScripts = [];
        $this->inlineStyles = [];
    }

    /**
     * Build an HTML tag.
     *
     * @param string $tag  Tag name
     * @param array<string, string> $attrs  Attributes
     * @param string|null $content  Content (null = self-closing)
     * @return string  HTML tag
     */
    protected function buildTag(string $tag, array $attrs, ?string $content = null): string
    {
        $attrStr = '';
        foreach ($attrs as $key => $value) {
            $attrStr .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        if ($content === null) {
            return "<{$tag}{$attrStr}>";
        }

        return "<{$tag}{$attrStr}>{$content}</{$tag}>";
    }
}
