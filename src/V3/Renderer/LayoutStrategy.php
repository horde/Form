<?php
declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3\Renderer;

/**
 * LayoutStrategy interface for determining form layout structure.
 *
 * Different strategies control how form fields are arranged:
 * - TableLayout: 2-column table (lib/ compatible)
 * - DivLayout: Modern CSS Grid/Flexbox
 * - ListLayout: Semantic dl/dt/dd
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
interface LayoutStrategy
{
    /**
     * Wrap a single form field (label + control + help + error).
     *
     * @param string $label  Field label HTML
     * @param string $control  Field control HTML
     * @param string $help  Help text HTML
     * @param string $error  Error message HTML
     * @param array<string, mixed> $meta  Additional metadata
     * @return string  Wrapped field HTML
     */
    public function wrapField(
        string $label,
        string $control,
        string $help = '',
        string $error = '',
        array $meta = []
    ): string;

    /**
     * Wrap a form section (group of fields).
     *
     * @param string $title  Section title
     * @param string $description  Section description
     * @param string $content  Section content (wrapped fields)
     * @param array<string, mixed> $meta  Additional metadata
     * @return string  Wrapped section HTML
     */
    public function wrapSection(
        string $title,
        string $description,
        string $content,
        array $meta = []
    ): string;

    /**
     * Wrap the entire form structure.
     *
     * @param string $header  Form header HTML
     * @param string $fields  All fields HTML
     * @param string $buttons  Form buttons HTML
     * @param array<string, mixed> $meta  Additional metadata
     * @return string  Wrapped form HTML
     */
    public function wrapForm(
        string $header,
        string $fields,
        string $buttons,
        array $meta = []
    ): string;
}
