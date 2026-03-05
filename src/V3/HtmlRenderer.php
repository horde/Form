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
     * Control rendering mode.
     *
     * - 'modern': HTML5 native controls (date, time, datetime-local)
     * - 'legacy': JavaScript-based controls (jQuery UI, Flatpickr, etc.)
     * - 'fallback': Plain text inputs with patterns
     */
    protected string $controlMode = 'modern';

    /**
     * Construct a new HTML renderer.
     *
     * @param ControlRenderer|null $controlRenderer  Control renderer
     * @param LayoutStrategy|null $layoutStrategy  Layout strategy
     * @param ErrorRenderer|null $errorRenderer  Error renderer
     * @param AssetManager|null $assetManager  Asset manager
     * @param array<string, mixed> $config  Configuration options
     */
    public function __construct(
        ?ControlRenderer $controlRenderer = null,
        ?LayoutStrategy $layoutStrategy = null,
        ?ErrorRenderer $errorRenderer = null,
        ?AssetManager $assetManager = null,
        array $config = []
    ) {
        // Extract controlMode from config before parent constructor
        if (isset($config['controlMode'])) {
            $this->controlMode = (string)$config['controlMode'];
        }

        // Call parent constructor (this will initialize $this->assetManager)
        parent::__construct($controlRenderer, $layoutStrategy, $errorRenderer, $assetManager, $config);

        // Now configure the control renderer with our settings
        if ($this->controlRenderer instanceof HtmlControlRenderer) {
            $this->controlRenderer->setControlMode($this->controlMode);
            $this->controlRenderer->setAssetManager($this->assetManager);
        }
    }

    /**
     * Create default control renderer.
     */
    protected function createDefaultControlRenderer(): ControlRenderer
    {
        // Note: AssetManager will be set after construction via setAssetManager()
        return new HtmlControlRenderer(
            $this->requiredMarker,
            $this->helpMarker,
            $this->controlMode,
            null  // AssetManager will be set in constructor
        );
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

    /**
     * Set control rendering mode.
     *
     * @param string $mode  Control mode: modern, legacy, fallback
     */
    public function setControlMode(string $mode): void
    {
        $this->controlMode = $mode;

        // Update control renderer if it supports control mode
        if ($this->controlRenderer instanceof HtmlControlRenderer) {
            $this->controlRenderer->setControlMode($mode);
        }
    }

    /**
     * Get control rendering mode.
     *
     * @return string  Control mode
     */
    public function getControlMode(): string
    {
        return $this->controlMode;
    }
}
