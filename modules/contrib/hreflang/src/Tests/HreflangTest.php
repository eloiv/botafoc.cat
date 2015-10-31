<?php
/**
 * @file
 * Definition of Drupal\language\Tests\LanguageConfigurationTest.
 */

namespace Drupal\hreflang\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for presence of the hreflang link element.
 *
 * @group Alternate hreflang
 */
class HreflangTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hreflang', 'language');

  /**
   * Functional tests for the hreflang tag.
   */
  function testHreflangTag() {
    global $base_url;
    // User to add language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);
    // Add predefined language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $this->drupalGet('admin');
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/admin" />', 'French hreflang found on English page.');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />', 'English hreflang found on English page.');
    $this->drupalGet('fr/admin');
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/admin" />', 'French hreflang found on French page.');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />', 'English hreflang found on French page.');

    // Disable URL detection and enable session detection.
    $edit = array(
      'language_interface[enabled][language-url]' => FALSE,
      'language_interface[enabled][language-session]' => '1',
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->drupalGet('admin');
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />', 'French hreflang found on default page.');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />', 'English hreflang found on default page.');
    $this->drupalGet('admin', array('query' => array('language' => 'en')));
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />', 'French hreflang found on English page.');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin?language=en" />', 'English hreflang found on English page.');
    $this->drupalGet('admin', array('query' => array('language' => 'fr')));
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />', 'French hreflang found on French page.');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin?language=en" />', 'English hreflang found on French page.');
  }
}
