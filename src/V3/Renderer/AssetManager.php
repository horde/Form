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
