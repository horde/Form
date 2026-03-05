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
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
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
     * Control rendering mode.
     *
     * - 'modern': HTML5 native controls (date, time, datetime-local)
     * - 'legacy': JavaScript-based controls (jQuery UI, Flatpickr, etc.)
     * - 'fallback': Plain text inputs with patterns
     *
     * @var string
     */
    protected string $controlMode = 'modern';

    /**
     * Asset manager for adding JS/CSS dependencies.
     */
    protected ?AssetManager $assetManager = null;

    /**
     * Construct renderer.
     *
     * @param string $requiredMarker  Required field marker
     * @param string $helpMarker  Help text marker
     * @param string $controlMode  Control rendering mode (modern|legacy|fallback)
     * @param AssetManager|null $assetManager  Asset manager for legacy mode
     */
    public function __construct(
        string $requiredMarker = '*',
        string $helpMarker = '?',
        string $controlMode = 'modern',
        ?AssetManager $assetManager = null
    ) {
        $this->requiredMarker = $requiredMarker;
        $this->helpMarker = $helpMarker;
        $this->controlMode = $controlMode;
        $this->assetManager = $assetManager;
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
     *
     * Supports three rendering modes:
     * - modern: HTML5 <input type="date">
     * - legacy: JavaScript datepicker
     * - fallback: Plain text with pattern validation
     */
    protected function renderDate(Variable $var, Form $form, bool $readonly): string
    {
        return match($this->controlMode) {
            'legacy' => $this->renderDateLegacy($var, $form, $readonly),
            'fallback' => $this->renderDateFallback($var, $form, $readonly),
            default => $this->renderDateModern($var, $form, $readonly)
        };
    }

    /**
     * Render date input using HTML5 native control.
     */
    protected function renderDateModern(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'date',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatDateValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render date input using JavaScript datepicker.
     */
    protected function renderDateLegacy(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        // Add datepicker assets
        if ($this->assetManager) {
            $this->assetManager->addStylesheet('datepicker.css');
            $this->assetManager->addScript('datepicker.js');
        }

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'class' => 'datepicker',
            'value' => $this->formatDateValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
            'data-date-format' => 'yyyy-mm-dd',
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render date input as plain text with pattern validation.
     */
    protected function renderDateFallback(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatDateValue($value),
            'pattern' => '\d{4}-\d{2}-\d{2}',
            'placeholder' => 'YYYY-MM-DD',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render time input.
     *
     * Supports three rendering modes:
     * - modern: HTML5 <input type="time">
     * - legacy: JavaScript timepicker
     * - fallback: Plain text with pattern validation
     */
    protected function renderTime(Variable $var, Form $form, bool $readonly): string
    {
        return match($this->controlMode) {
            'legacy' => $this->renderTimeLegacy($var, $form, $readonly),
            'fallback' => $this->renderTimeFallback($var, $form, $readonly),
            default => $this->renderTimeModern($var, $form, $readonly)
        };
    }

    /**
     * Render time input using HTML5 native control.
     */
    protected function renderTimeModern(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'time',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatTimeValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render time input using JavaScript timepicker.
     */
    protected function renderTimeLegacy(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        // Add timepicker assets
        if ($this->assetManager) {
            $this->assetManager->addStylesheet('timepicker.css');
            $this->assetManager->addScript('timepicker.js');
        }

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'class' => 'timepicker',
            'value' => $this->formatTimeValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
            'data-time-format' => 'HH:mm',
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render time input as plain text with pattern validation.
     */
    protected function renderTimeFallback(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatTimeValue($value),
            'pattern' => '\d{2}:\d{2}',
            'placeholder' => 'HH:MM',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render datetime input.
     *
     * Supports three rendering modes:
     * - modern: HTML5 <input type="datetime-local">
     * - legacy: JavaScript datetimepicker
     * - fallback: Plain text with pattern validation
     */
    protected function renderDatetime(Variable $var, Form $form, bool $readonly): string
    {
        return match($this->controlMode) {
            'legacy' => $this->renderDatetimeLegacy($var, $form, $readonly),
            'fallback' => $this->renderDatetimeFallback($var, $form, $readonly),
            default => $this->renderDatetimeModern($var, $form, $readonly)
        };
    }

    /**
     * Render datetime input using HTML5 native control.
     */
    protected function renderDatetimeModern(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'datetime-local',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatDatetimeValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render datetime input using JavaScript datetimepicker.
     */
    protected function renderDatetimeLegacy(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        // Add datetimepicker assets
        if ($this->assetManager) {
            $this->assetManager->addStylesheet('datetimepicker.css');
            $this->assetManager->addScript('datetimepicker.js');
        }

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'class' => 'datetimepicker',
            'value' => $this->formatDatetimeValue($value),
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
            'data-datetime-format' => 'yyyy-mm-dd HH:mm',
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render datetime input as plain text with pattern validation.
     */
    protected function renderDatetimeFallback(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $this->formatDatetimeValue($value),
            'pattern' => '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}',
            'placeholder' => 'YYYY-MM-DDTHH:MM',
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
     * Format date value for HTML input.
     *
     * Converts various date formats to YYYY-MM-DD.
     *
     * @param mixed $value  Date value (timestamp, DateTime, string)
     * @return string  Formatted date or empty string
     */
    protected function formatDateValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        // Handle DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Handle timestamps
        if (is_numeric($value)) {
            return date('Y-m-d', (int)$value);
        }

        // Handle string dates
        if (is_string($value)) {
            // Try to parse as date
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return (string)$value;
    }

    /**
     * Format time value for HTML input.
     *
     * Converts various time formats to HH:MM.
     *
     * @param mixed $value  Time value (timestamp, DateTime, string)
     * @return string  Formatted time or empty string
     */
    protected function formatTimeValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        // Handle DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        // Handle timestamps
        if (is_numeric($value)) {
            return date('H:i', (int)$value);
        }

        // Handle string times
        if (is_string($value)) {
            // Already in HH:MM format?
            if (preg_match('/^\d{2}:\d{2}$/', $value)) {
                return $value;
            }

            // Try to parse as time (need full date context for AM/PM parsing)
            $timestamp = strtotime('today ' . $value);
            if ($timestamp !== false) {
                return date('H:i', $timestamp);
            }
        }

        return (string)$value;
    }

    /**
     * Format datetime value for HTML input.
     *
     * Converts various datetime formats to YYYY-MM-DDTHH:MM.
     *
     * @param mixed $value  Datetime value (timestamp, DateTime, string)
     * @return string  Formatted datetime or empty string
     */
    protected function formatDatetimeValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        // Handle DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d\TH:i');
        }

        // Handle timestamps
        if (is_numeric($value)) {
            return date('Y-m-d\TH:i', (int)$value);
        }

        // Handle string datetimes
        if (is_string($value)) {
            // Try to parse as datetime
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d\TH:i', $timestamp);
            }
        }

        return (string)$value;
    }

    /**
     * Render address input (textarea with address parsing).
     */
    protected function renderAddress(Variable $var, Form $form, bool $readonly): string
    {
        // Address uses longtext textarea
        return $this->renderLongtext($var, $form, $readonly);
    }

    /**
     * Render phone number input.
     */
    protected function renderPhone(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'tel',  // HTML5 tel input type
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        // Add size attribute if available
        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        }

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render cellphone number input.
     */
    protected function renderCellphone(Variable $var, Form $form, bool $readonly): string
    {
        // Cellphone uses same rendering as phone
        return $this->renderPhone($var, $form, $readonly);
    }

    /**
     * Render country dropdown.
     */
    protected function renderCountry(Variable $var, Form $form, bool $readonly): string
    {
        // Country extends enum, use enum renderer
        return $this->renderEnum($var, $form, $readonly);
    }

    /**
     * Render credit card number input.
     */
    protected function renderCreditcard(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'pattern' => '[0-9]{13,19}',  // Credit cards are 13-19 digits
            'inputmode' => 'numeric',
            'autocomplete' => 'cc-number',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render link/URL input.
     */
    protected function renderLink(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'url',  // HTML5 URL input type
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        // Add size attribute if available
        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        }

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render IP address input.
     */
    protected function renderIpaddress(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'pattern' => '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$',
            'placeholder' => '192.168.1.1',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render color picker.
     */
    protected function renderColorpicker(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'color',  // HTML5 color input type
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value ?: '#000000',  // Default to black if empty
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render header (display-only text with heading styling).
     */
    protected function renderHeader(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        return sprintf('<h3 class="form-header">%s</h3>', htmlspecialchars((string)$value));
    }

    /**
     * Render description (display-only text).
     */
    protected function renderDescription(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        return sprintf('<p class="form-description">%s</p>', htmlspecialchars((string)$value));
    }

    /**
     * Render spacer (visual separator).
     */
    protected function renderSpacer(Variable $var, Form $form, bool $readonly): string
    {
        return '<hr class="form-spacer">';
    }

    /**
     * Render invalid field (always fails validation).
     */
    protected function renderInvalid(Variable $var, Form $form, bool $readonly): string
    {
        return '<em class="invalid-field">Invalid field</em>';
    }

    /**
     * Render HTML content (raw HTML display).
     */
    protected function renderHtml(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        // Raw HTML - use with caution!
        return '<div class="html-content">' . $value . '</div>';
    }

    /**
     * Render image upload.
     */
    protected function renderImage(Variable $var, Form $form, bool $readonly): string
    {
        if ($readonly) {
            $value = $this->getValue($var, $form);
            if ($value) {
                return sprintf('<img src="%s" alt="Uploaded image" class="form-image">', htmlspecialchars($value));
            }
            return '<em>No image</em>';
        }

        $attrs = [
            'type' => 'file',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'accept' => 'image/*',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render month/year selector.
     */
    protected function renderMonthyear(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'month',  // HTML5 month input type
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
     * Render set (checkbox group).
     */
    protected function renderSet(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $values = method_exists($var, 'getValues') ? $var->getValues() : [];

        // Handle null values
        if (!is_array($values)) {
            $values = [];
        }

        $selected = is_array($value) ? $value : [];

        $output = [];
        foreach ($values as $key => $label) {
            $id = $this->getFieldId($var, true);
            $isChecked = in_array($key, $selected, true);

            $attrs = [
                'type' => 'checkbox',
                'name' => $var->getVarName() . '[]',
                'id' => $id,
                'value' => $key,
                'checked' => $isChecked ? 'checked' : null,
                'disabled' => $var->isDisabled() || $readonly ? 'disabled' : null,
            ];

            $output[] = sprintf(
                '<div class="checkbox">%s <label for="%s">%s</label></div>',
                $this->buildTag('input', $attrs),
                htmlspecialchars($id),
                htmlspecialchars($label)
            );
        }

        return implode("\n", $output);
    }

    /**
     * Render octal number input.
     */
    protected function renderOctal(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'pattern' => '[0-7]+',
            'placeholder' => '755',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render password confirmation (two password fields).
     */
    protected function renderPasswordconfirm(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $varName = $var->getVarName();

        if ($readonly) {
            return '<em>Password (hidden)</em>';
        }

        $output = [];

        // Original password
        $attrs1 = [
            'type' => 'password',
            'name' => $varName . '[original]',
            'id' => $this->getFieldId($var) . '_original',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
            'autocomplete' => 'new-password',
        ];
        $output[] = '<div class="password-confirm-field">';
        $output[] = '<label for="' . htmlspecialchars($attrs1['id']) . '">Password:</label> ';
        $output[] = $this->buildTag('input', $attrs1);
        $output[] = '</div>';

        // Confirmation password
        $attrs2 = [
            'type' => 'password',
            'name' => $varName . '[confirm]',
            'id' => $this->getFieldId($var) . '_confirm',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
            'autocomplete' => 'new-password',
        ];
        $output[] = '<div class="password-confirm-field">';
        $output[] = '<label for="' . htmlspecialchars($attrs2['id']) . '">Confirm:</label> ';
        $output[] = $this->buildTag('input', $attrs2);
        $output[] = '</div>';

        return implode("\n", $output);
    }

    /**
     * Render email confirmation (two email fields).
     */
    protected function renderEmailconfirm(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $varName = $var->getVarName();

        if ($readonly) {
            $email = is_array($value) ? ($value['original'] ?? '') : $value;
            return htmlspecialchars((string)$email);
        }

        $output = [];

        // Original email
        $attrs1 = [
            'type' => 'email',
            'name' => $varName . '[original]',
            'id' => $this->getFieldId($var) . '_original',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];
        $output[] = '<div class="email-confirm-field">';
        $output[] = '<label for="' . htmlspecialchars($attrs1['id']) . '">Email:</label> ';
        $output[] = $this->buildTag('input', $attrs1);
        $output[] = '</div>';

        // Confirmation email
        $attrs2 = [
            'type' => 'email',
            'name' => $varName . '[confirm]',
            'id' => $this->getFieldId($var) . '_confirm',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];
        $output[] = '<div class="email-confirm-field">';
        $output[] = '<label for="' . htmlspecialchars($attrs2['id']) . '">Confirm:</label> ';
        $output[] = $this->buildTag('input', $attrs2);
        $output[] = '</div>';

        return implode("\n", $output);
    }

    /**
     * Render stringlist (text input for list of strings).
     */
    protected function renderStringlist(Variable $var, Form $form, bool $readonly): string
    {
        // Stringlist extends TextVariable, use text renderer
        return $this->renderText($var, $form, $readonly);
    }

    /**
     * Render stringarray (textarea for array of strings).
     */
    protected function renderStringarray(Variable $var, Form $form, bool $readonly): string
    {
        // Similar to longtext but for arrays
        return $this->renderLongtext($var, $form, $readonly);
    }

    /**
     * Render intlist (text input for list of integers).
     */
    protected function renderIntlist(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'pattern' => '^[0-9, ]+$',
            'placeholder' => '1, 2, 3, 4',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render hourminutesecond (time with seconds).
     */
    protected function renderHourminutesecond(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'time',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'step' => '1',  // Enable seconds
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render monthdayyear (date selector with separate fields).
     */
    protected function renderMonthdayyear(Variable $var, Form $form, bool $readonly): string
    {
        // Use standard date input (HTML5 provides built-in picker)
        return $this->renderDate($var, $form, $readonly);
    }

    /**
     * Render countedtext (text input with character counter).
     */
    protected function renderCountedtext(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $maxlength = method_exists($var, 'getMaxLength') ? $var->getMaxLength() : null;

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        if ($maxlength) {
            $attrs['maxlength'] = $maxlength;
        }

        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        }

        $input = $this->buildTag('input', $attrs);

        // Add character counter
        if ($maxlength && !$readonly) {
            $counterId = $this->getFieldId($var) . '_counter';
            $currentLength = strlen((string)$value);
            return $input . sprintf(
                ' <span id="%s" class="char-counter">%d/%d</span>',
                htmlspecialchars($counterId),
                $currentLength,
                $maxlength
            );
        }

        return $input;
    }

    /**
     * Render IPv6 address input.
     */
    protected function renderIp6address(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'pattern' => '^([0-9a-fA-F]{0,4}:){7}[0-9a-fA-F]{0,4}$',
            'placeholder' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        if (method_exists($var, 'getSize')) {
            $attrs['size'] = $var->getSize();
        } else {
            $attrs['size'] = 39;  // Max IPv6 length
        }

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render selectfiles (file multi-select).
     */
    protected function renderSelectfiles(Variable $var, Form $form, bool $readonly): string
    {
        if ($readonly) {
            return '<em>File selection (readonly)</em>';
        }

        $attrs = [
            'type' => 'file',
            'name' => $var->getVarName() . '[]',
            'id' => $this->getFieldId($var),
            'multiple' => 'multiple',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render sound (audio file upload).
     */
    protected function renderSound(Variable $var, Form $form, bool $readonly): string
    {
        if ($readonly) {
            $value = $this->getValue($var, $form);
            if ($value) {
                return sprintf('<audio controls><source src="%s"></audio>', htmlspecialchars($value));
            }
            return '<em>No audio</em>';
        }

        $attrs = [
            'type' => 'file',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'accept' => 'audio/*',
            'required' => $var->required ? 'required' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render addresslink (address with link).
     */
    protected function renderAddresslink(Variable $var, Form $form, bool $readonly): string
    {
        // Similar to address but with link functionality
        return $this->renderAddress($var, $form, $readonly);
    }

    /**
     * Render mlenum (multi-language enum).
     */
    protected function renderMlenum(Variable $var, Form $form, bool $readonly): string
    {
        // Similar to enum
        return $this->renderEnum($var, $form, $readonly);
    }

    /**
     * Render category (hierarchical category selector).
     */
    protected function renderCategory(Variable $var, Form $form, bool $readonly): string
    {
        // Render as enum/select for now
        return $this->renderEnum($var, $form, $readonly);
    }

    /**
     * Render sorter (drag-drop list sorter).
     */
    protected function renderSorter(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);
        $values = method_exists($var, 'getValues') ? $var->getValues() : [];

        if (!is_array($values)) {
            $values = [];
        }

        // Render as multi-select for now (JS enhancement would add drag-drop)
        $output = [];
        $output[] = '<div class="sorter-list" data-sortable="true">';

        foreach ($values as $key => $label) {
            $output[] = sprintf(
                '<div class="sorter-item" data-value="%s">%s</div>',
                htmlspecialchars((string)$key),
                htmlspecialchars($label)
            );
        }

        $output[] = '</div>';

        // Hidden field to store order
        $output[] = sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($var->getVarName()),
            htmlspecialchars($this->getFieldId($var)),
            htmlspecialchars(json_encode($value))
        );

        return implode("\n", $output);
    }

    /**
     * Render assign (assign items between two lists).
     */
    protected function renderAssign(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        // Render as multi-select for now
        $output = [];
        $output[] = '<div class="assign-container">';
        $output[] = '<div class="assign-available"><strong>Available:</strong></div>';
        $output[] = '<div class="assign-selected"><strong>Selected:</strong></div>';
        $output[] = '</div>';

        // Hidden field for selected values
        $output[] = sprintf(
            '<input type="hidden" name="%s" id="%s" value="%s">',
            htmlspecialchars($var->getVarName()),
            htmlspecialchars($this->getFieldId($var)),
            htmlspecialchars(json_encode($value))
        );

        return implode("\n", $output);
    }

    /**
     * Render matrix (matrix/grid selection).
     */
    protected function renderMatrix(Variable $var, Form $form, bool $readonly): string
    {
        // Render as placeholder for now (complex grid UI)
        return '<div class="matrix-container"><em>Matrix input (requires JavaScript)</em></div>';
    }

    /**
     * Render tableset (tabular data input).
     */
    protected function renderTableset(Variable $var, Form $form, bool $readonly): string
    {
        // Render as placeholder for now (complex table UI)
        return '<div class="tableset-container"><em>Tableset input (requires JavaScript)</em></div>';
    }

    /**
     * Render dblookup (database lookup field).
     */
    protected function renderDblookup(Variable $var, Form $form, bool $readonly): string
    {
        // Render as text input with autocomplete attributes
        $value = $this->getValue($var, $form);

        $attrs = [
            'type' => 'text',
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'value' => $value,
            'class' => 'dblookup',
            'autocomplete' => 'off',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('input', $attrs);
    }

    /**
     * Render obrowser (object browser).
     */
    protected function renderObrowser(Variable $var, Form $form, bool $readonly): string
    {
        // Render as button that opens browser
        $value = $this->getValue($var, $form);

        $output = [];
        $output[] = sprintf(
            '<input type="text" name="%s" id="%s" value="%s" readonly>',
            htmlspecialchars($var->getVarName()),
            htmlspecialchars($this->getFieldId($var)),
            htmlspecialchars((string)$value)
        );

        if (!$readonly) {
            $output[] = sprintf(
                ' <button type="button" class="obrowser-button" data-target="%s">Browse...</button>',
                htmlspecialchars($this->getFieldId($var))
            );
        }

        return implode('', $output);
    }

    /**
     * Render captcha (CAPTCHA field).
     */
    protected function renderCaptcha(Variable $var, Form $form, bool $readonly): string
    {
        if ($readonly) {
            return '<em>CAPTCHA (readonly)</em>';
        }

        $output = [];
        $output[] = '<div class="captcha-container">';
        $output[] = '<div class="captcha-image"><em>[CAPTCHA Image]</em></div>';
        $output[] = sprintf(
            '<input type="text" name="%s" id="%s" placeholder="Enter code" required autocomplete="off">',
            htmlspecialchars($var->getVarName()),
            htmlspecialchars($this->getFieldId($var))
        );
        $output[] = '</div>';

        return implode("\n", $output);
    }

    /**
     * Render figlet (ASCII art text).
     */
    protected function renderFiglet(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        // Display as preformatted text
        return sprintf('<pre class="figlet">%s</pre>', htmlspecialchars((string)$value));
    }

    /**
     * Render PGP key field.
     */
    protected function renderPgp(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'rows' => 10,
            'cols' => 80,
            'class' => 'pgp-key',
            'placeholder' => '-----BEGIN PGP PUBLIC KEY BLOCK-----',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('textarea', $attrs, htmlspecialchars((string)$value));
    }

    /**
     * Render S/MIME certificate field.
     */
    protected function renderSmime(Variable $var, Form $form, bool $readonly): string
    {
        $value = $this->getValue($var, $form);

        $attrs = [
            'name' => $var->getVarName(),
            'id' => $this->getFieldId($var),
            'rows' => 10,
            'cols' => 80,
            'class' => 'smime-cert',
            'placeholder' => '-----BEGIN CERTIFICATE-----',
            'required' => $var->required ? 'required' : null,
            'readonly' => $readonly ? 'readonly' : null,
            'disabled' => $var->isDisabled() ? 'disabled' : null,
        ];

        return $this->buildTag('textarea', $attrs, htmlspecialchars((string)$value));
    }

    /**
     * Render keyvalmultienum (key-value pair multi-select).
     */
    protected function renderKeyvalmultienum(Variable $var, Form $form, bool $readonly): string
    {
        // Similar to multienum
        return $this->renderMultienum($var, $form, $readonly);
    }

    /**
     * Get control rendering mode.
     *
     * @return string  Control mode (modern|legacy|fallback)
     */
    public function getControlMode(): string
    {
        return $this->controlMode;
    }

    /**
     * Set control rendering mode.
     *
     * @param string $mode  Control mode (modern|legacy|fallback)
     */
    public function setControlMode(string $mode): void
    {
        $this->controlMode = $mode;
    }

    /**
     * Set asset manager for legacy mode.
     *
     * @param AssetManager $assetManager  Asset manager
     */
    public function setAssetManager(AssetManager $assetManager): void
    {
        $this->assetManager = $assetManager;
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
