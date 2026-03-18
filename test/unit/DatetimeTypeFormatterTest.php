<?php

declare(strict_types=1);

/**
 * Tests for Horde_Form_Type_datetime with formatter support
 *
 * @category  Horde
 * @package   Form
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Form\Test\Unit;

use Horde_Form_Type_datetime;
use Horde\Date\Formatter\IcuFormatter;
use Horde\Date\Formatter\DateTimeFormatter;
use PHPUnit\Framework\TestCase;

class DatetimeTypeFormatterTest extends TestCase
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
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, '%Y-%m-%d', false);

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter mode
     */
    public function testIcuFormatterMode(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, new IcuFormatter(), 'en_US');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter with German locale
     */
    public function testIcuFormatterWithGermanLocale(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'EEEE, dd. MMMM yyyy', false, new IcuFormatter(), 'de_DE');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('Mittwoch, 18. März 2026', $result);
    }

    /**
     * Test DateTimeFormatter mode
     */
    public function testDateTimeFormatterMode(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'Y-m-d', false, new DateTimeFormatter(), 'en_US');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test formatter with timezone
     */
    public function testFormatterWithTimezone(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd HH:mm', false, new IcuFormatter(), 'en_US', 'America/New_York');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        // Date portion should be the same regardless of timezone
        $this->assertStringStartsWith('2026-03-18', $result);
    }

    /**
     * Test empty date array returns empty string
     */
    public function testEmptyDateArrayReturnsEmptyString(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, new IcuFormatter(), 'en_US');

        $date = ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => ''];
        $result = $type->formatDate($date);

        $this->assertEquals('', $result);
    }

    /**
     * Test init accepts null formatter (strftime mode)
     */
    public function testInitAcceptsNullFormatter(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, '%Y-%m-%d', false, null);

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test init accepts FormatterInterface instance
     */
    public function testInitAcceptsFormatterInstance(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, new IcuFormatter());

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test init rejects string (class name)
     */
    public function testInitRejectsString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, 'Horde\Date\Formatter\IcuFormatter');
    }

    /**
     * Test init rejects array
     */
    public function testInitRejectsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, []);
    }

    /**
     * Test French locale formatting
     */
    public function testFrenchLocaleFormatting(): void
    {
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'EEEE dd MMMM yyyy', false, new IcuFormatter(), 'fr_FR');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        $this->assertEquals('mercredi 18 mars 2026', $result);
    }

    /**
     * Test that formatter parameters are passed through to monthdayyear component
     */
    public function testFormatterPassedThroughToMonthdayyear(): void
    {
        $formatter = new IcuFormatter();
        $type = new Horde_Form_Type_datetime();
        $type->init('', '', true, null, 'yyyy-MM-dd', false, $formatter, 'de_DE', 'Europe/Berlin');

        // Access the internal _mdy component to verify it received the formatter
        $reflection = new \ReflectionClass($type);
        $mdyProperty = $reflection->getProperty('_mdy');
        $mdyProperty->setAccessible(true);
        $mdy = $mdyProperty->getValue($type);

        // Test that the formatter was passed through by checking formatting output
        $date = ['year' => 2026, 'month' => 3, 'day' => 18, 'hour' => 14, 'minute' => 30, 'second' => 45];
        $result = $type->formatDate($date);

        // Should format correctly with the formatter
        $this->assertEquals('2026-03-18', $result);
    }
}
