<?php

/**
 * @file
 * Contains \Drupal\coffee\Tests\CoffeeTest.
 */

namespace Drupal\coffee\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Coffee module functionality.
 *
 * @group coffee
 */
class CoffeeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['coffee', 'menu_ui'];

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $coffeeUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $coffeeAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp(){
    parent::setUp();

    $this->webUser = $this->drupalCreateUser();
    $this->coffeeUser = $this->drupalCreateUser(['access coffee']);
    $this->coffeeAdmin = $this->drupalCreateUser(['administer coffee']);
  }

  /**
   * Tests coffee configuration form.
   */
  public function testCoffeeConfiguration() {
    $this->drupalGet('admin/config/user-interface/coffee');
    $this->assertResponse(403);

    $this->drupalLogin($this->coffeeAdmin);
    $this->drupalGet('admin/config/user-interface/coffee');
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-coffee-menus-admin', 'The admin menu is enabled by default');

    $edit = [
      'coffee_menus[tools]' => 'tools',
      'coffee_menus[account]' => 'account'
    ];
    $this->drupalPostForm('admin/config/user-interface/coffee', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $expected = [
      'admin' => 'admin',
      'tools' => 'tools',
      'account' => 'account'
    ];
    $config = \Drupal::config('coffee.configuration')->get('coffee_menus');
    $this->assertEqual($expected, $config, 'The configuration options have been properly saved');
  }

  /**
   * Tests that the coffee assets are loaded properly.
   */
  public function testCoffeeAssets() {
    // Ensure that the coffee assets are not loaded for users without the
    // adequate permission.
    $this->drupalGet('');
    $this->assertNoRaw('modules/coffee/js/coffee.js');

    // Ensure that the coffee assets are loaded properly for users with the
    // adequate permission.
    $this->drupalLogin($this->coffeeUser);
    $this->drupalGet('');
    $this->assertRaw('modules/coffee/js/coffee.js');

    // Ensure that the coffee assets are not loaded for users without the
    // adequate permission.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('');
    $this->assertNoRaw('modules/coffee/js/coffee.js');
  }

  /**
   * Tests that the toolbar integration works properly.
   */
  public function testCoffeeToolbarIntegration() {
    \Drupal::service('module_installer')->install(['toolbar']);
    $tab_xpath = '//nav[@id="toolbar-bar"]//div/a[contains(@class, "toolbar-icon-coffee")]';

    $toolbar_user = $this->drupalCreateUser(['access toolbar']);
    $this->drupalLogin($toolbar_user);
    $this->assertRaw('id="toolbar-administration"');
    $this->assertFalse($this->xpath($tab_xpath), 'Not found the Coffee toolbar tab.');

    $coffee_toolbar_user = $this->drupalCreateUser(['access toolbar', 'access coffee']);
    $this->drupalLogin($coffee_toolbar_user);
    $this->assertRaw('id="toolbar-administration"');
    $this->assertEqual(count($this->xpath($tab_xpath)), 1, 'Found the Coffee toolbar tab.');
  }

}
