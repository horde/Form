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

namespace Horde\Form\V3\Renderer;

use Horde\Form\V3\Variable;
use Horde\Form\Form;

/**
 * HTML control renderer for rendering form controls as HTML.
 *
 * Renders each variable type as appropriate HTML form controls:
 * - Text → <input type="text">
 * - Enum → <select>
 * - Boolean → <input type="checkbox">
 * - Date → date picker
 * - etc.
 *
 * Supports 58 variable types with appropriate HTML controls.
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
class HtmlControlRenderer implements ControlRenderer
{
    /**
     * Required field marker.
     */
    protected string $requiredMarker = '*';

    /**
     * Help text marker.
     */
    protected string $helpMarker = '?';

    /**
     * Field ID cache.
     *
     * @var array<string, int>
     */
    protected array $fieldIds = [];

    /**
     * Construct renderer.
     *
     * @param string $requiredMarker  Required field marker
     * @param string $helpMarker  Help text marker
     */
    public function __construct(string $requiredMarker = '*', string $helpMarker = '?')
    {
        $this->requiredMarker = $requiredMarker;
        $this->helpMarker = $helpMarker;
    }

    /**
     * Render a form control for a variable.
     */
    public function renderControl(Variable $var, Form $form, bool $readonly = false): string
    {
        // Get variable type name
        $typeName = $var->getTypeName();

        // Try type-specific render method
        $method = 'render' . ucfirst($typeName);
        if (method_exists($this, $method)) {
            return $this->$method($var, $form, $readonly);
        }

        // Fallback to text input
        return $this->renderText($var, $form, $readonly);
    }

    /**
     * Render a label for a variable.
     */
    public function renderLabel(Variable $var, Form $form): string
    {
        $label = htmlspecialchars($var->getHumanName());

        // Add required marker
        if ($var->required) {
            $label .= ' <span class="required">' . $this->requiredMarker . '</span>';
        }

        $fieldId = $this->getFieldId($var);

        return sprintf(
            '<label for="%s">%s</label>',
            htmlspecialchars($fieldId),
            $label
        );
    }

    /**
     * Render help text for a variable.
     */
    public function renderHelp(Variable $var): string
    {
        if (!$var->hasDescription()) {
            return '';
        }

        $description = htmlspecialchars($var->getDescription());

        return sprintf(
            '<span class="help-text">%s %s</span>',
            $this->helpMarker,
            $description
        );
    }

    /**
     * Generate a unique field ID for a variable.
     */
    public function getFieldId(Variable $var, bool $new = false): string
    {
        $varName = $var->getVarName();

        if ($new || !isset($this->fieldIds[$varName])) {
            $this->fieldIds[$varName] = 0;
        } else {
            $this->fieldIds[$varName]++;
        }

        $id = str_replace(['[', ']', ' '], ['_', '', '_'], $varName);
        if ($this->fieldIds[$varName] > 0) {
            $id .= '_' . $this->fieldIds[$varName];
        }

        return $id;
    }

    /**
     * Build an HTML tag with attributes.
     *
     * @param string $tag  Tag name
     * @param array<string, mixed> $attrs  Attributes
     * @param string|null $content  Content (null = self-closing)
     * @return string  HTML tag
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
            return "<{$tag}{$attrStr}>";
        }

        return "<{$tag}{$attrStr}>{$content}</{$tag}>";
    }

    /**
     * Get current value for a variable.
     *
     * @param Variable $var  Variable
     * @param Form $form  Form
     * @return mixed  Current value
     */
    protected function getValue(Variable $var, Form $form)
    {
        $vars = $form->getVars();
        return $var->getValue(new \Horde_Variables($vars));
    }

    // ========================================================================
    // Variable type renderers
    // ========================================================================

    /**
     * Render text input.
     */
    protected function renderText(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        // Text-specific attributes
        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        }
        if (method_exists($var, 'getMaxLength')) {
            $maxlen = $var->getMaxLength();
            if ($maxlen) {
                $attrs['maxlength'] = $maxlen;
            }
        }

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render enum (select dropdown).
     */
    protected function renderEnum(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $values = method_exists($var, 'getValues') ? $var->getValues() : [];

        $attrs = [
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
        ];

        $options = [];

        // Optional prompt
        if (method_exists($var, 'getPrompt')) {
            $prompt = $var->getPrompt();
            if ($prompt) {
                $options[] = sprintf(
                    '<option value="">%s</option>',
                    htmlspecialchars($prompt)
                );
            }
        }

        // Options
        foreach ($values as $key => $label) {
            $selected = (string)$key === (string)$value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars((string)$key),
                $selected,
                htmlspecialchars($label)
            );
        }

        return sprintf(
            '<select%s>%s</select>',
            $this->buildAttrs($attrs),
            implode('', $options)
        );
    }

    /**
     * Render boolean (checkbox).
     */
    protected function renderBoolean(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'checkbox',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => '1',
            'checked' => $value ? 'checked' : null,
            'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render longtext (textarea).
     */
    protected function renderLongtext(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        // Longtext-specific attributes
        if (method_exists($var, 'getRows')) {
            $attrs['rows'] = $var->getRows();
        } else {
            $attrs['rows'] = 8;
        }
        if (method_exists($var, 'getCols')) {
            $attrs['cols'] = $var->getCols();
        } else {
            $attrs['cols'] = 80;
        }

        return $this->buildTag('textarea', $attrs, htmlspecialchars((string)$value));
    }

    /**
     * Render email input.
     */
    protected function renderEmail(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'email',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render number input.
     */
    protected function renderNumber(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'number',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render int input.
     */
    protected function renderInt(Variable $var, Form $form, bool $readonly): string
    {
        return $this->renderNumber($var, $form, $readonly);
    }

    /**
     * Render password input.
     */
    protected function renderPassword(Variable $var, Form $form, bool $readonly): string
    {
        $attrs = [
            'type' => 'password',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render file upload.
     */
    protected function renderFile(Variable $var, Form $form, bool $readonly): string
    {
        if ($readonly) {
            return '<em>File upload (readonly)</em>';
        }

        $attrs = [
            'type' => 'file',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render date input.
     */
    protected function renderDate(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'date',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render time input.
     */
    protected function renderTime(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'time',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render datetime input.
     */
    protected function renderDatetime(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'datetime-local',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render radio buttons.
     */
    protected function renderRadio(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $values = method_exists($var, 'getValues') ? $var->getValues() : [];

        $output = [];
        foreach ($values as $key => $label) {
            $id = $this->getFieldId($var, true);
            $attrs = [
                'type' => 'radio',
                'name' => $var->getVarName(),
                'id' => $id,
                'value' => $key,
                'checked' => (string)$key === (string)$value ? 'checked' : null,
                'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
            ];

            $output[] = sprintf(
                '<div class="radio">%s <label for="%s">%s</label></div>',
                $this->buildTag('input', $attrs),
                htmlspecialchars($id),
                htmlspecialchars($label)
            );
        }

        return implode("\n", $output);
    }

    /**
     * Render multienum (multi-select).
     */
    protected function renderMultienum(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $values = method_exists($var, 'getValues') ? $var->getValues() : [];
        $selected = is_array($value) ? $value : [];

        $attrs = [
            'name' => $var->getVarName() . '[]',
            'id' => $this->getFieldId($var),
            'multiple' => 'multiple',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
        ];

        // Size attribute
        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        } else {
            $attrs['size'] = min(count($values), 10);
        }

        $options = [];
        foreach ($values as $key => $label) {
            $isSelected = in_array($key, $selected, true) ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars((string)$key),
                $isSelected,
                htmlspecialchars($label)
            );
        }

        return sprintf(
            '<select%s>%s</select>',
            $this->buildAttrs($attrs),
            implode('', $options)
        );
    }

    /**
     * Build attribute string.
     *
     * @param array<string, mixed> $attrs  Attributes
     * @return string  Attribute string
     */
    protected function buildAttrs(array $attrs): string
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
        return $attrStr;
    }
}
