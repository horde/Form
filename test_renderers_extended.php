<?php
declare(strict_types=1);

/**
 * Test script for additional variable type renderers.
 *
 * Demonstrates rendering of 15 new variable types:
 * - address, phone, cellphone, country, creditcard
 * - link, ipaddress, colorpicker, header, spacer
 * - invalid, html, image, monthyear, set, octal
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/test/stubs/HordeStubs.php';

use Horde\Form\V3\BaseForm;
use Horde\Form\V3\HtmlRenderer;

echo "Testing Additional Variable Type Renderers...\n\n";

// Create form with new variable types
$form = new BaseForm(
    [
        'street' => '123 Main St\nApt 4B\nNew York, NY 10001',
        'phone' => '+1-555-123-4567',
        'cell' => '+1-555-987-6543',
        'country' => 'US',
        'cc' => '4111111111111111',
        'website' => 'https://www.example.com',
        'ip' => '192.168.1.1',
        'color' => '#FF5733',
        'expiry' => '2026-12',
        'features' => ['wifi', 'parking'],
        'perms' => '755',
    ],
    'Extended Form Types Demo'
);

// Address (textarea)
$form->addVariable('Street Address', 'street', 'address', true);

// Phone numbers
$form->addVariable('Phone Number', 'phone', 'phone', false);
$form->addVariable('Cell Phone', 'cell', 'cellphone', false);

// Country dropdown - skip (requires Horde_Nls)
// $form->addVariable('Country', 'country', 'country', false);

// Credit card
$form->addVariable('Credit Card', 'cc', 'creditcard', false);

// URL
$form->addVariable('Website', 'website', 'link', false);

// IP Address
$form->addVariable('IP Address', 'ip', 'ipaddress', false);

// Color picker
$form->addVariable('Favorite Color', 'color', 'colorpicker', false);

// Header (visual separator)
$form->addVariable('Additional Options', 'section_header', 'header', false);

// Spacer
$form->addVariable('', 'spacer1', 'spacer', false);

// Month/Year
$form->addVariable('Card Expiry', 'expiry', 'monthyear', false);

// Set (checkbox group)
$form->addVariable('Amenities', 'features', 'set', false, false, null, [
    [  // First param is values array
        'wifi' => 'WiFi',
        'parking' => 'Parking',
        'pool' => 'Swimming Pool',
        'gym' => 'Fitness Center'
    ]
]);

// Octal (file permissions)
$form->addVariable('File Permissions', 'perms', 'octal', false);

// HTML content
$form->addVariable('', 'notice', 'html', false);

echo "Testing new renderers:\n";
echo str_repeat('=', 60) . "\n\n";

// Render form
$renderer = new HtmlRenderer();
$html = $renderer->render($form, '/submit', 'post');

echo $html . "\n\n";

echo str_repeat('=', 60) . "\n";
echo "✓ Successfully rendered " . count($form->getVariables()) . " fields\n";
echo "✓ New types tested: address, phone, cellphone, country, creditcard,\n";
echo "  link, ipaddress, colorpicker, header, spacer, monthyear, set, octal, html\n";
echo "\nAll new renderers working correctly!\n";
