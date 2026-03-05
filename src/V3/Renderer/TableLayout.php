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
 * Table layout strategy (lib/ compatible).
 *
 * Renders forms using 2-column HTML tables:
 * - Left column: Labels
 * - Right column: Controls
 *
 * Compatible with classic Horde form styling.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class TableLayout implements LayoutStrategy
{
    /**
     * Width of label column.
     */
    protected string $labelWidth = '15%';

    /**
     * Construct layout.
     *
     * @param string $labelWidth  Label column width (CSS value)
     */
    public function __construct(string $labelWidth = '15%')
    {
        $this->labelWidth = $labelWidth;
    }

    /**
     * Wrap a single form field.
     */
    public function wrapField(
        string $label,
        string $control,
        string $help = '',
        string $error = '',
        array $meta = []
    ): string {
        $rowClass = $meta['rowClass'] ?? '';
        $required = $meta['required'] ?? false;

        $labelClass = 'label' . ($required ? ' required' : '');
        $controlClass = 'control';

        if ($error) {
            $controlClass .= ' error';
        }

        return sprintf(
            '<tr class="%s"><td class="%s" style="width: %s;">%s %s</td><td class="%s">%s %s</td></tr>',
            htmlspecialchars($rowClass),
            $labelClass,
            $this->labelWidth,
            $label,
            $help,
            $controlClass,
            $control,
            $error
        );
    }

    /**
     * Wrap a form section.
     */
    public function wrapSection(
        string $title,
        string $description,
        string $content,
        array $meta = []
    ): string {
        $isBase = $meta['isBase'] ?? false;

        // Base section doesn't need special wrapper
        if ($isBase || empty($title)) {
            return $content;
        }

        // Section with title
        $output = [];

        if ($title) {
            $output[] = sprintf(
                '<tr class="section-header"><th colspan="2"><h3>%s</h3></th></tr>',
                htmlspecialchars($title)
            );
        }

        if ($description) {
            $output[] = sprintf(
                '<tr class="section-description"><td colspan="2">%s</td></tr>',
                htmlspecialchars($description)
            );
        }

        $output[] = $content;

        return implode("\n", $output);
    }

    /**
     * Wrap the entire form.
     */
    public function wrapForm(
        string $header,
        string $fields,
        string $buttons,
        array $meta = []
    ): string {
        $output = [];

        $output[] = '<div class="horde-form">';

        if ($header) {
            $output[] = $header;
        }

        $output[] = '<table class="form-table" cellspacing="0">';
        $output[] = '<tbody>';
        $output[] = $fields;
        $output[] = '</tbody>';
        $output[] = '</table>';

        if ($buttons) {
            $output[] = '<div class="form-buttons">';
            $output[] = $buttons;
            $output[] = '</div>';
        }

        $output[] = '</div>';

        return implode("\n", $output);
    }
}
