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
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Form
 */

namespace Horde\Form\V3;

use Horde\Form\Form;
use Horde\Form\V3\Renderer\ControlRenderer;
use Horde\Form\V3\Renderer\LayoutStrategy;
use Horde\Form\V3\Renderer\ErrorRenderer;
use Horde\Form\V3\Renderer\AssetManager;

/**
 * Base implementation of the Renderer interface.
 *
 * Provides common functionality for all renderers. Subclasses implement
 * specific rendering strategies (HTML, JSON, etc.).
 *
 * Uses template method pattern - defines the rendering flow, subclasses
 * implement specific rendering logic.
 *
 * PSR-4 implementation.
 *
 * @see Horde_Form_Renderer PSR-0 legacy equivalent in lib/Horde/Form/Renderer.php
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
abstract class BaseRenderer implements Renderer
{
    /**
     * Control renderer for form controls.
     */
    protected ControlRenderer $controlRenderer;

    /**
     * Layout strategy for form structure.
     */
    protected LayoutStrategy $layoutStrategy;

    /**
     * Error renderer for validation errors.
     */
    protected ErrorRenderer $errorRenderer;

    /**
     * Asset manager for JS/CSS.
     */
    protected AssetManager $assetManager;

    /**
     * Show form header (title, description)?
     */
    protected bool $showHeader = true;

    /**
     * Required field marker.
     */
    protected string $requiredMarker = '*';

    /**
     * Help text marker.
     */
    protected string $helpMarker = '?';

    /**
     * Encode form title with htmlspecialchars()?
     */
    protected bool $encodeTitle = true;

    /**
     * Use striped rows (alternate colors)?
     */
    protected bool $stripedRows = false;

    /**
     * Current row index (for striped rows).
     */
    protected int $currentRow = 0;

    /**
     * Form name being rendered.
     */
    protected string $formName = '';

    /**
     * Construct a new renderer.
     *
     * @param ControlRenderer|null $controlRenderer  Control renderer
     * @param LayoutStrategy|null $layoutStrategy  Layout strategy
     * @param ErrorRenderer|null $errorRenderer  Error renderer
     * @param AssetManager|null $assetManager  Asset manager
     * @param array<string, mixed> $config  Configuration options
      *
      * @api
     */
    public function __construct(
        ?ControlRenderer $controlRenderer = null,
        ?LayoutStrategy $layoutStrategy = null,
        ?ErrorRenderer $errorRenderer = null,
        ?AssetManager $assetManager = null,
        array $config = []
    ) {
        // Set defaults if not provided (will be overridden by subclasses)
        $this->controlRenderer = $controlRenderer ?? $this->createDefaultControlRenderer();
        $this->layoutStrategy = $layoutStrategy ?? $this->createDefaultLayoutStrategy();
        $this->errorRenderer = $errorRenderer ?? $this->createDefaultErrorRenderer();
        $this->assetManager = $assetManager ?? $this->createDefaultAssetManager();

        // Apply configuration
        if (isset($config['showHeader'])) {
            $this->showHeader = (bool)$config['showHeader'];
        }
        if (isset($config['requiredMarker'])) {
            $this->requiredMarker = (string)$config['requiredMarker'];
        }
        if (isset($config['helpMarker'])) {
            $this->helpMarker = (string)$config['helpMarker'];
        }
        if (isset($config['encodeTitle'])) {
            $this->encodeTitle = (bool)$config['encodeTitle'];
        }
        if (isset($config['stripedRows'])) {
            $this->stripedRows = (bool)$config['stripedRows'];
        }
    }

    /**
     * Create default control renderer (overrideable).
     *
     * @return ControlRenderer
     */
    abstract protected function createDefaultControlRenderer(): ControlRenderer;

    /**
     * Create default layout strategy (overrideable).
     *
     * @return LayoutStrategy
     */
    abstract protected function createDefaultLayoutStrategy(): LayoutStrategy;

    /**
     * Create default error renderer (overrideable).
     *
     * @return ErrorRenderer
     */
    abstract protected function createDefaultErrorRenderer(): ErrorRenderer;

    /**
     * Create default asset manager (overrideable).
     *
     * @return AssetManager
     */
    abstract protected function createDefaultAssetManager(): AssetManager;

    /**
     * Render the complete form.
     *
     * Template method - defines the rendering flow.
      *
      * @api
     */
    public function render(Form $form, string $action = '', string $method = 'post'): string
    {
        $this->formName = $form->getName();
        $this->currentRow = 0;

        $output = [];

        // Opening form tag
        $output[] = $this->renderOpen($form, $action, $method);

        // Form header (title, description)
        if ($this->showHeader) {
            $output[] = $this->renderHeader($form);
        }

        // Validation errors (if any)
        $output[] = $this->renderErrors($form);

        // Hidden fields
        $output[] = $this->renderHidden($form);

        // Render variables by section
        $variables = $form->getVariables(flat: false);

        if (empty($variables)) {
            // No sections, render as flat list
            $fields = $this->renderVariables($form->getVariables(flat: true), $form);
            $output[] = $this->layoutStrategy->wrapForm(
                header: '',
                fields: $fields,
                buttons: $this->renderButtons($form),
                meta: ['formName' => $form->getName()]
            );
        } else {
            // Has sections
            $sections = [];
            foreach ($variables as $sectionName => $sectionVars) {
                $sections[] = $this->renderSection($sectionName, $sectionVars, $form);
            }

            $output[] = $this->layoutStrategy->wrapForm(
                header: '',
                fields: implode("\n", $sections),
                buttons: $this->renderButtons($form),
                meta: ['formName' => $form->getName()]
            );
        }

        // Closing form tag
        $output[] = $this->renderClose();

        // Assets (JS/CSS)
        $output[] = $this->assetManager->render();

        return implode("\n", array_filter($output));
    }

    /**
     * Render multiple variables.
     *
     * @param array<Variable> $variables  Variables to render
     * @param Form $form  Parent form
     * @return string  Rendered variables
      *
      * @internal
     */
    protected function renderVariables(array $variables, Form $form): string
    {
        $output = [];
        foreach ($variables as $var) {
            $output[] = $this->renderVariable($var, $form);
        }
        return implode("\n", $output);
    }

    /**
     * Render a single variable.
      *
      * @api
     */
    public function renderVariable(Variable $variable, Form $form): string
    {
        // Skip hidden variables (rendered separately)
        if ($variable->isHidden()) {
            return '';
        }

        // Render components
        $label = $this->controlRenderer->renderLabel($variable, $form);
        $control = $this->controlRenderer->renderControl($variable, $form, $variable->readonly);
        $help = $this->controlRenderer->renderHelp($variable);

        // Get error for this field (if any)
        $error = '';
        $fieldError = $form->getError($variable->getVarName());
        if ($fieldError) {
            $error = $this->errorRenderer->renderFieldError($variable->getVarName(), $fieldError);
        }

        // Row metadata
        $meta = [
            'varName' => $variable->getVarName(),
            'required' => $variable->required,
            'readonly' => $variable->readonly,
            'disabled' => $variable->isDisabled(),
            'rowClass' => $this->getRowClass(),
        ];

        $this->currentRow++;

        return $this->layoutStrategy->wrapField($label, $control, $help, $error, $meta);
    }

    /**
     * Render a form section.
      *
      * @api
     */
    public function renderSection(string|int $sectionName, array $variables, Form $form): string
    {
        $title = is_string($sectionName) && $sectionName !== '__base' ? $sectionName : '';
        $description = $title ? $form->getSectionDesc($sectionName) : '';

        $content = $this->renderVariables($variables, $form);

        $meta = [
            'sectionName' => $sectionName,
            'isBase' => $sectionName === '__base',
        ];

        return $this->layoutStrategy->wrapSection($title, $description, $content, $meta);
    }

    /**
     * Render validation errors.
      *
      * @api
     */
    public function renderErrors(Form $form): string
    {
        $errors = $form->getErrors();
        if (empty($errors)) {
            return '';
        }

        return $this->errorRenderer->renderFormErrors($errors);
    }

    /**
     * Render hidden fields.
      *
      * @api
     */
    public function renderHidden(Form $form): string
    {
        $output = [];

        // Form name (for submission detection)
        $output[] = sprintf(
            '<input type="hidden" name="formname" value="%s">',
            htmlspecialchars($form->getName())
        );

        // Hidden variables
        $vars = $form->getVars();
        $varsObject = new \Horde_Variables($vars);
        $hiddenVars = $form->getVariables(flat: true, withHidden: true);
        foreach ($hiddenVars as $var) {
            if ($var->isHidden()) {
                $value = $var->getValue($varsObject);
                $output[] = sprintf(
                    '<input type="hidden" name="%s" value="%s">',
                    htmlspecialchars($var->getVarName()),
                    htmlspecialchars((string)$value)
                );
            }
        }

        return implode("\n", $output);
    }

    /**
     * Get CSS class for current row (for striped rows).
     *
     * @return string  CSS class
      *
      * @internal
     */
    protected function getRowClass(): string
    {
        if (!$this->stripedRows) {
            return '';
        }

        return $this->currentRow % 2 === 0 ? 'even' : 'odd';
    }

    /**
     * Build an HTML tag with attributes.
     *
     * @param string $tag  Tag name
     * @param array<string, mixed> $attrs  Attributes (null values are skipped)
     * @param string|null $content  Tag content (null = self-closing)
     * @return string  HTML tag
      *
      * @internal
     */
    protected function buildTag(string $tag, array $attrs = [], ?string $content = null): string
    {
        $attrStr = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $attrStr .= ' ' . htmlspecialchars($key);
            } else {
                $attrStr .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars((string)$value));
            }
        }

        if ($content === null) {
            // Self-closing tag
            return "<{$tag}{$attrStr}>";
        }

        return "<{$tag}{$attrStr}>{$content}</{$tag}>";
    }

    /**
     * Set whether to show form header.
      *
      * @api
     */
    public function setShowHeader(bool $show): void
    {
        $this->showHeader = $show;
    }

    /**
     * Set required field marker.
      *
      * @api
     */
    public function setRequiredMarker(string $marker): void
    {
        $this->requiredMarker = $marker;
    }

    /**
     * Set help text marker.
      *
      * @api
     */
    public function setHelpMarker(string $marker): void
    {
        $this->helpMarker = $marker;
    }

    /**
     * Set whether to encode form title.
      *
      * @api
     */
    public function setEncodeTitle(bool $encode): void
    {
        $this->encodeTitle = $encode;
    }

    /**
     * Get control renderer.
      *
      * @api
     */
    public function getControlRenderer(): ControlRenderer
    {
        return $this->controlRenderer;
    }

    /**
     * Get layout strategy.
      *
      * @api
     */
    public function getLayoutStrategy(): LayoutStrategy
    {
        return $this->layoutStrategy;
    }

    /**
     * Get error renderer.
      *
      * @api
     */
    public function getErrorRenderer(): ErrorRenderer
    {
        return $this->errorRenderer;
    }

    /**
     * Get asset manager.
      *
      * @api
     */
    public function getAssetManager(): AssetManager
    {
        return $this->assetManager;
    }
}
