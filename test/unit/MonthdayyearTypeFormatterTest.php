<?php

declare(strict_types=1);

/**
 * Tests for Horde_Form_Type_monthdayyear with formatter support
 *
 * @category  Horde
 * @package   Form
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Form\Test\Unit;

use Horde_Form_Type_monthdayyear;
use Horde\Date\Formatter\IcuFormatter;
use Horde\Date\Formatter\DateTimeFormatter;
use PHPUnit\Framework\TestCase;

class MonthdayyearTypeFormatterTest extends TestCase
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
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, '%Y-%m-%d');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter mode
     */
    public function testIcuFormatterMode(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test ICU formatter with German locale
     */
    public function testIcuFormatterWithGermanLocale(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'EEEE, dd. MMMM yyyy', new IcuFormatter(), 'de_DE');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('Mittwoch, 18. März 2026', $result);
    }

    /**
     * Test DateTimeFormatter mode
     */
    public function testDateTimeFormatterMode(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'Y-m-d', new DateTimeFormatter(), 'en_US');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test formatter with timezone
     */
    public function testFormatterWithTimezone(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd HH:mm', new IcuFormatter(), 'en_US', 'America/New_York');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        // Date portion should be the same regardless of timezone
        $this->assertStringStartsWith('2026-03-18', $result);
    }

    /**
     * Test empty date array returns empty string
     */
    public function testEmptyDateArrayReturnsEmptyString(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');

        $date = ['year' => '', 'month' => '', 'day' => ''];
        $result = $type->formatDate($date);

        $this->assertEquals('', $result);
    }

    /**
     * Test init accepts null formatter (strftime mode)
     */
    public function testInitAcceptsNullFormatter(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, '%Y-%m-%d', null);

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test init accepts FormatterInterface instance
     */
    public function testInitAcceptsFormatterInstance(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter());

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
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

        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd', 'Horde\Date\Formatter\IcuFormatter');
    }

    /**
     * Test init rejects array
     */
    public function testInitRejectsArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must be null or an instance of FormatterInterface');

        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'yyyy-MM-dd', []);
    }

    /**
     * Test French locale formatting
     */
    public function testFrenchLocaleFormatting(): void
    {
        $type = new Horde_Form_Type_monthdayyear();
        $type->init('', '', true, null, 'EEEE dd MMMM yyyy', new IcuFormatter(), 'fr_FR');

        $date = ['year' => 2026, 'month' => 3, 'day' => 18];
        $result = $type->formatDate($date);

        $this->assertEquals('mercredi 18 mars 2026', $result);
    }

    // ===== Input Type Tests =====

    /**
     * Test formatDate accepts date array in both modes
     */
    public function testFormatDateAcceptsArrayInBothModes(): void
    {
        // Strftime mode
        $type1 = new Horde_Form_Type_monthdayyear();
        $type1->init('', '', true, null, '%Y-%m-%d', null);
        $this->assertEquals('2026-03-18', $type1->formatDate(['year' => 2026, 'month' => 3, 'day' => 18]));

        // Formatter mode
        $type2 = new Horde_Form_Type_monthdayyear();
        $type2->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');
        $this->assertEquals('2026-03-18', $type2->formatDate(['year' => 2026, 'month' => 3, 'day' => 18]));
    }

    /**
     * Test formatDate accepts Horde_Date object in both modes
     */
    public function testFormatDateAcceptsHordeDateInBothModes(): void
    {
        $date = new \Horde_Date('2026-03-18');

        // Strftime mode
        $type1 = new Horde_Form_Type_monthdayyear();
        $type1->init('', '', true, null, '%Y-%m-%d', null);
        $this->assertEquals('2026-03-18', $type1->formatDate($date));

        // Formatter mode
        $type2 = new Horde_Form_Type_monthdayyear();
        $type2->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');
        $this->assertEquals('2026-03-18', $type2->formatDate($date));
    }

    /**
     * Test formatDate accepts ISO date string in both modes
     */
    public function testFormatDateAcceptsIsoStringInBothModes(): void
    {
        // Strftime mode - accepts various string formats
        $type1 = new Horde_Form_Type_monthdayyear();
        $type1->init('', '', true, null, '%Y-%m-%d', null);
        $this->assertEquals('2026-03-18', $type1->formatDate('2026-03-18'));

        // Formatter mode - also accepts string formats through getDateOb()
        $type2 = new Horde_Form_Type_monthdayyear();
        $type2->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');
        $this->assertEquals('2026-03-18', $type2->formatDate('2026-03-18'));
    }

    /**
     * Test formatDate accepts UNIX timestamp in both modes
     */
    public function testFormatDateAcceptsTimestampInBothModes(): void
    {
        $timestamp = strtotime('2026-03-18');

        // Strftime mode
        $type1 = new Horde_Form_Type_monthdayyear();
        $type1->init('', '', true, null, '%Y-%m-%d', null);
        $this->assertEquals('2026-03-18', $type1->formatDate($timestamp));

        // Formatter mode
        $type2 = new Horde_Form_Type_monthdayyear();
        $type2->init('', '', true, null, 'yyyy-MM-dd', new IcuFormatter(), 'en_US');
        $this->assertEquals('2026-03-18', $type2->formatDate($timestamp));
    }
}
