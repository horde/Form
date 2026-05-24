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
 * AssetManager interface for managing JavaScript and CSS assets.
 *
 * Handles loading and rendering of form-related assets:
 * - JavaScript files
 * - CSS stylesheets
 * - Inline scripts
 * - Inline styles
 *
 * ## Implementations
 *
 * **HtmlAssetManager** (default): Self-contained. Collects assets and
 * renders them as inline `<script>` / `<link>` tags appended after the
 * form HTML. Suitable for standalone pages, headless rendering, and
 * testing.
 *
 * **PageOutputAssetManager** (in horde/core): Delegates all calls to
 * Core's AssetCollector. The `render()` method returns an empty string
 * because assets are rendered by PageComposer in `<head>` or at
 * end-of-body. Use this when rendering forms inside a full Horde page.
 *
 * ## Injection
 *
 * Pass an AssetManager to HtmlRenderer's constructor:
 *
 *     // Page-integrated (assets go to <head> / deferred foot):
 *     $am = new PageOutputAssetManager($assetCollector);
 *     $renderer = new HtmlRenderer(assetManager: $am);
 *
 *     // Self-contained (default, assets rendered inline):
 *     $renderer = new HtmlRenderer();
 *
 * ## Implementing custom AssetManagers
 *
 * Implementations may return an empty string from `render()` if assets
 * are rendered elsewhere (e.g., by a page-level composer). The
 * BaseRenderer calls `render()` at end-of-form and appends the result;
 * an empty string simply means no inline output.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
interface AssetManager
{
    /**
     * Add a JavaScript file.
     *
     * @param string $file  File path/URL
     * @param array<string, string> $attrs  Additional attributes (async, defer, etc.)
     */
    public function addScript(string $file, array $attrs = []): void;

    /**
     * Add a CSS stylesheet.
     *
     * @param string $file  File path/URL
     * @param array<string, string> $attrs  Additional attributes (media, etc.)
     */
    public function addStylesheet(string $file, array $attrs = []): void;

    /**
     * Add inline JavaScript code.
     *
     * @param string $code  JavaScript code
     */
    public function addInlineScript(string $code): void;

    /**
     * Add inline CSS code.
     *
     * @param string $code  CSS code
     */
    public function addInlineStyle(string $code): void;

    /**
     * Render all assets as HTML.
     *
     * @return string  HTML with <script> and <link> tags
     */
    public function render(): string;

    /**
     * Clear all assets.
     */
    public function clear(): void;
}
