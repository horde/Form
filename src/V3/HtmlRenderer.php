<?php
declare(strict_types=1);

/**
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

use Horde\Form\V3\Renderer\HtmlControlRenderer;
use Horde\Form\V3\Renderer\TableLayout;
use Horde\Form\V3\Renderer\InlineErrorRenderer;
use Horde\Form\V3\Renderer\HtmlAssetManager;
use Horde\Form\V3\Renderer\ControlRenderer;
use Horde\Form\V3\Renderer\LayoutStrategy;
use Horde\Form\V3\Renderer\ErrorRenderer;
use Horde\Form\V3\Renderer\AssetManager;

/**
 * HTML renderer for forms.
 *
 * Renders forms as interactive HTML with JavaScript support.
 *
 * Modes:
 * - active: Editable form (default)
 * - inactive: Display-only (no editing)
 * - print: Printer-friendly (no JS)
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class HtmlRenderer extends BaseRenderer
{
    /**
     * Rendering mode.
     */
    protected string $mode = 'active';

    /**
     * Create default control renderer.
     */
    protected function createDefaultControlRenderer(): ControlRenderer
    {
        return new HtmlControlRenderer($this->requiredMarker, $this->helpMarker);
    }

    /**
     * Create default layout strategy.
     */
    protected function createDefaultLayoutStrategy(): LayoutStrategy
    {
        return new TableLayout();
    }

    /**
     * Create default error renderer.
     */
    protected function createDefaultErrorRenderer(): ErrorRenderer
    {
        return new InlineErrorRenderer();
    }

    /**
     * Create default asset manager.
     */
    protected function createDefaultAssetManager(): AssetManager
    {
        return new HtmlAssetManager();
    }

    /**
     * Render form opening tag.
     */
    public function renderOpen(\Horde\Form\Form $form, string $action, string $method): string
    {
        $name = $form->getName();
        $enctype = $form->getEnctype();

        $attrs = [
            'action' => $action,
            'method' => strtolower($method),
            'name' => $name,
            'id' => $name,
        ];

        if ($enctype) {
            $attrs['enctype'] = $enctype;
        }

        return $this->buildTag('form', $attrs, null);
    }

    /**
     * Render form closing tag.
     */
    public function renderClose(): string
    {
        return '</form>';
    }

    /**
     * Render form header.
     */
    public function renderHeader(\Horde\Form\Form $form): string
    {
        $title = $form->getTitle();
        if (empty($title)) {
            return '';
        }

        if ($this->encodeTitle) {
            $title = htmlspecialchars($title);
        }

        $output = [];
        $output[] = '<div class="form-header">';
        $output[] = sprintf('<h2>%s</h2>', $title);

        $extra = $form->getExtra();
        if ($extra) {
            $output[] = '<div class="form-extra">' . $extra . '</div>';
        }

        $output[] = '</div>';

        return implode("\n", $output);
    }

    /**
     * Render form buttons.
     */
    public function renderButtons(\Horde\Form\Form $form): string
    {
        // Get buttons via reflection (BaseForm doesn't expose submit/reset yet)
        // For now, render a default submit button
        $output = [];

        $output[] = '<div class="form-buttons-inner">';
        $output[] = $this->buildTag('input', [
            'type' => 'submit',
            'value' => 'Submit',
            'class' => 'button'
        ]);
        $output[] = '</div>';

        return implode("\n", $output);
    }

    /**
     * Set rendering mode.
     *
     * @param string $mode  Mode: active, inactive, print
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Get rendering mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}
