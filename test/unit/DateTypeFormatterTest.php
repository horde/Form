<?php

declare(strict_types=1);

/**
 * Tests for Horde_Form_Type_date with formatter support
 *
 * @category  Horde
 * @package   Form
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Form\Test\Unit;

use Horde_Form_Type_date;
use Horde\Date\Formatter\IcuFormatter;
use Horde\Date\Formatter\DateTimeFormatter;
use PHPUnit\Framework\TestCase;

class DateTypeFormatterTest extends TestCase
{
    protected string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    /**
     * Test default strftime mode (backward compatibility)
     */
    public function testDefaultStrftimeMode(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('%Y-%m-%d');

        $timestamp = strtotime('2026-03-18 14:30:00');
        $result = $type->getFormattedTime($timestamp, null, false);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter mode
     */
    public function testIcuFormatterMode(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter(), 'en_US');

        $timestamp = strtotime('2026-03-18 14:30:00');
        $result = $type->getFormattedTime($timestamp, null, false);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter with locale
     */
    public function testIcuFormatterWithLocale(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('EEEE, dd. MMMM yyyy', new IcuFormatter(), 'de_DE');

        $timestamp = strtotime('2026-03-18');
        $result = $type->getFormattedTime($timestamp, null, false);

        $this->assertEquals('Mittwoch, 18. März 2026', $result);
    }

    /**
     * Test DateTimeFormatter mode
     */
    public function testDateTimeFormatterMode(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('Y-m-d H:i', new DateTimeFormatter(), 'en_US');

        $timestamp = strtotime('2026-03-18 14:30:00');
        $result = $type->getFormattedTime($timestamp, null, false);

        $this->assertEquals('2026-03-18 14:30', $result);
    }

    /**
     * Test formatter with timezone
     */
    public function testFormatterWithTimezone(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd HH:mm', new IcuFormatter(), 'en_US', 'America/New_York');

        $timestamp = strtotime('2026-03-18 12:00:00 UTC');
        $result = $type->getFormattedTime($timestamp, null, false);

        // UTC 12:00 should be earlier in New York (EST/EDT)
        $this->assertNotEquals('2026-03-18 12:00', $result);
    }

    /**
     * Test that formatter mode throws exception on non-timestamp
     */
    public function testFormatterModeRequiresTimestamp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter mode requires an integer timestamp');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter(), 'en_US');

        // Pass a string instead of timestamp - should throw
        $type->getFormattedTime('2026-03-18', null, false);
    }

    // ===== Formatter Parameter Guard Tests =====

    /**
     * Test init accepts null formatter (strftime mode)
     */
    public function testInitAcceptsNullFormatter(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('%Y-%m-%d', null);

        $timestamp = strtotime('2026-03-18');
        $result = $type->getFormattedTime($timestamp, null, false);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test init accepts FormatterInterface instance
     */
    public function testInitAcceptsFormatterInstance(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $timestamp = strtotime('2026-03-18');
        $result = $type->getFormattedTime($timestamp, null, false);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test init rejects string (class name)
     */
    public function testInitRejectsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', 'Horde\Date\Formatter\IcuFormatter');
    }

    /**
     * Test init rejects array
     */
    public function testInitRejectsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', []);
    }

    /**
     * Test init rejects stdClass
     */
    public function testInitRejectsStdClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new \stdClass());
    }

    // ===== Timestamp Type Guard Tests =====

    /**
     * Test strftime mode accepts integer timestamp
     */
    public function testStrftimeModeAcceptsIntegerTimestamp(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('%Y-%m-%d', null);

        $timestamp = strtotime('2026-03-18');
        $result = $type->getFormattedTime($timestamp, null, false);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test formatter mode accepts integer timestamp
     */
    public function testFormatterModeAcceptsIntegerTimestamp(): void
    {
        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $timestamp = strtotime('2026-03-18');
        $result = $type->getFormattedTime($timestamp, null, false);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test formatter mode rejects string timestamp
     */
    public function testFormatterModeRejectsStringTimestamp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter mode requires an integer timestamp, got string');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $timestamp = (string)strtotime('2026-03-18');
        $type->getFormattedTime($timestamp, null, false);
    }

    /**
     * Test formatter mode rejects date string
     */
    public function testFormatterModeRejectsDateString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter mode requires an integer timestamp, got string');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $type->getFormattedTime('2026-03-18', null, false);
    }

    /**
     * Test formatter mode rejects array
     */
    public function testFormatterModeRejectsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter mode requires an integer timestamp, got array');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $type->getFormattedTime(['year' => 2026, 'month' => 3, 'day' => 18], null, false);
    }

    /**
     * Test formatter mode rejects float
     */
    public function testFormatterModeRejectsFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter mode requires an integer timestamp, got double');

        $type = new Horde_Form_Type_date();
        $type->init('yyyy-MM-dd', new IcuFormatter());

        $type->getFormattedTime(1742565045.5, null, false);
    }
}
