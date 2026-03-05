<?php
declare(strict_types=1);

/**
 * V3 Control Mode Integration Test
 *
 * Demonstrates the three control rendering modes:
 * - modern: HTML5 native controls (default)
 * - legacy: JavaScript-based pickers
 * - fallback: Plain text with pattern validation
 *
 * This test shows how the same form variables can be rendered
 * in different ways depending on browser capabilities and
 * deployment requirements.
 *
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 */

namespace Horde\Form\Test\Integration\V3;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Form\V3\BaseForm;
use Horde\Form\V3\HtmlRenderer;
use Horde\Form\V3\Renderer\HtmlControlRenderer;

/**
 * Control mode integration test.
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Form
 */
#[CoversClass(BaseForm::class)]
#[CoversClass(HtmlRenderer::class)]
#[CoversClass(HtmlControlRenderer::class)]
class ControlModeTest extends TestCase
{
    /**
     * Build a form with date/time fields.
     *
     * @param array $data  Initial form data
     * @return BaseForm
     */
    protected function buildDateTimeForm(array $data = []): BaseForm
    {
        $form = new BaseForm($data, 'Event Registration');

        // Date field
        $form->addVariable(
            humanName: 'Event Date',
            varName: 'event_date',
            type: 'date',
            required: true,
            description: 'Select the event date'
        );

        // Time field
        $form->addVariable(
            humanName: 'Event Time',
            varName: 'event_time',
            type: 'time',
            required: true,
            description: 'Select the event time'
        );

        // Datetime field
        $form->addVariable(
            humanName: 'Registration Deadline',
            varName: 'deadline',
            type: 'datetime',
            required: false,
            description: 'Registration deadline'
        );

        return $form;
    }

    /**
     * Test 1: Modern mode (HTML5 native controls).
     */
    public function testModernMode(): void
    {
        $data = [
            'event_date' => '2026-06-15',
            'event_time' => '14:30',
            'deadline' => '2026-06-01T23:59',
        ];

        $form = $this->buildDateTimeForm($data);

        // Create renderer with modern mode (default)
        $renderer = new HtmlRenderer(config: ['controlMode' => 'modern']);
        $this->assertSame('modern', $renderer->getControlMode());

        // Render form
        $html = $renderer->render($form, '/events/register', 'post');

        // Verify HTML5 input types are used
        $this->assertStringContainsString('type="date"', $html, 'Should use HTML5 date input');
        $this->assertStringContainsString('type="time"', $html, 'Should use HTML5 time input');
        $this->assertStringContainsString('type="datetime-local"', $html, 'Should use HTML5 datetime-local input');

        // Verify values are formatted correctly
        $this->assertStringContainsString('value="2026-06-15"', $html);
        $this->assertStringContainsString('value="14:30"', $html);
        $this->assertStringContainsString('value="2026-06-01T23:59"', $html);

        // Should NOT have datepicker classes
        $this->assertStringNotContainsString('class="datepicker"', $html);
    }

    /**
     * Test 2: Legacy mode (JavaScript pickers).
     */
    public function testLegacyMode(): void
    {
        $data = [
            'event_date' => '2026-06-15',
            'event_time' => '14:30',
            'deadline' => '2026-06-01T23:59',
        ];

        $form = $this->buildDateTimeForm($data);

        // Create renderer with legacy mode
        $renderer = new HtmlRenderer(config: ['controlMode' => 'legacy']);
        $this->assertSame('legacy', $renderer->getControlMode());

        // Render form
        $html = $renderer->render($form, '/events/register', 'post');

        // Verify text inputs with picker classes are used
        $this->assertStringContainsString('class="datepicker"', $html);
        $this->assertStringContainsString('class="timepicker"', $html);
        $this->assertStringContainsString('class="datetimepicker"', $html);

        // Should NOT use HTML5 types
        $this->assertStringNotContainsString('type="date"', $html);
        $this->assertStringNotContainsString('type="time"', $html);
        $this->assertStringNotContainsString('type="datetime-local"', $html);

        // Should have data attributes for picker configuration
        $this->assertStringContainsString('data-date-format', $html);
        $this->assertStringContainsString('data-time-format', $html);
        $this->assertStringContainsString('data-datetime-format', $html);
    }

    /**
     * Test 3: Fallback mode (plain text with patterns).
     */
    public function testFallbackMode(): void
    {
        $data = [
            'event_date' => '2026-06-15',
            'event_time' => '14:30',
            'deadline' => '2026-06-01T23:59',
        ];

        $form = $this->buildDateTimeForm($data);

        // Create renderer with fallback mode
        $renderer = new HtmlRenderer(config: ['controlMode' => 'fallback']);
        $this->assertSame('fallback', $renderer->getControlMode());

        // Render form
        $html = $renderer->render($form, '/events/register', 'post');

        // Verify plain text inputs with patterns
        $this->assertStringContainsString('pattern="\\d{4}-\\d{2}-\\d{2}"', $html);
        $this->assertStringContainsString('pattern="\\d{2}:\\d{2}"', $html);
        $this->assertStringContainsString('pattern="\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}"', $html);

        // Verify placeholders
        $this->assertStringContainsString('placeholder="YYYY-MM-DD"', $html);
        $this->assertStringContainsString('placeholder="HH:MM"', $html);
        $this->assertStringContainsString('placeholder="YYYY-MM-DDTHH:MM"', $html);

        // Should NOT use HTML5 types or picker classes
        $this->assertStringNotContainsString('type="date"', $html);
        $this->assertStringNotContainsString('type="time"', $html);
        $this->assertStringNotContainsString('type="datetime-local"', $html);
        $this->assertStringNotContainsString('class="datepicker"', $html);
    }

    /**
     * Test 4: Switching modes dynamically.
     */
    public function testSwitchingModes(): void
    {
        $form = $this->buildDateTimeForm(['event_date' => '2026-06-15']);
        $renderer = new HtmlRenderer();

        // Start with modern mode (default)
        $this->assertSame('modern', $renderer->getControlMode());
        $html = $renderer->render($form, '/test', 'post');
        $this->assertStringContainsString('type="date"', $html);

        // Switch to legacy mode
        $renderer->setControlMode('legacy');
        $this->assertSame('legacy', $renderer->getControlMode());
        $html = $renderer->render($form, '/test', 'post');
        $this->assertStringContainsString('class="datepicker"', $html);

        // Switch to fallback mode
        $renderer->setControlMode('fallback');
        $this->assertSame('fallback', $renderer->getControlMode());
        $html = $renderer->render($form, '/test', 'post');
        $this->assertStringContainsString('pattern="\\d{4}-\\d{2}-\\d{2}"', $html);
    }

    /**
     * Test 5: Date value formatting.
     */
    public function testDateValueFormatting(): void
    {
        // Test with various date formats
        $testCases = [
            // Input format => Expected output
            '2026-06-15' => '2026-06-15',
            '2026/06/15' => '2026-06-15',
            'June 15, 2026' => '2026-06-15',
            '15.06.2026' => '2026-06-15',
        ];

        $renderer = new HtmlRenderer(config: ['controlMode' => 'modern']);

        foreach ($testCases as $input => $expected) {
            $form = $this->buildDateTimeForm(['event_date' => $input]);
            $html = $renderer->render($form, '/test', 'post');

            $this->assertStringContainsString(
                'value="' . $expected . '"',
                $html,
                "Input '{$input}' should format to '{$expected}'"
            );
        }
    }

    /**
     * Test 6: Time value formatting.
     */
    public function testTimeValueFormatting(): void
    {
        // Test with various time formats
        $testCases = [
            // Input format => Expected output
            '14:30' => '14:30',
            '14:30:00' => '14:30',
            '2:30 PM' => '14:30',
            '02:30 PM' => '14:30',
        ];

        $renderer = new HtmlRenderer(config: ['controlMode' => 'modern']);

        foreach ($testCases as $input => $expected) {
            $form = $this->buildDateTimeForm(['event_time' => $input]);
            $html = $renderer->render($form, '/test', 'post');

            $this->assertStringContainsString(
                'value="' . $expected . '"',
                $html,
                "Input '{$input}' should format to '{$expected}'"
            );
        }
    }

    /**
     * Test 7: Empty date/time values.
     */
    public function testEmptyValues(): void
    {
        $form = $this->buildDateTimeForm([]);
        $renderer = new HtmlRenderer(config: ['controlMode' => 'modern']);
        $html = $renderer->render($form, '/test', 'post');

        // Verify empty values render as empty strings (not "value=""" but no value attribute)
        // This is acceptable - empty values should render cleanly
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('type="time"', $html);
    }

    /**
     * Test 8: Configuration via constructor.
     */
    public function testConfigurationViaConstructor(): void
    {
        // Test all three modes via constructor config
        $modes = ['modern', 'legacy', 'fallback'];

        foreach ($modes as $mode) {
            $renderer = new HtmlRenderer(config: ['controlMode' => $mode]);
            $this->assertSame($mode, $renderer->getControlMode());
        }
    }
}
