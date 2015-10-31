<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\MenuLinkTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Tests menu links deletion.
 *
 * @group multiversion
 */
class MenuLinkTest extends MultiversionWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'multiversion',
    'menu_link_content',
    'menu_ui',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(array('administer menu'));
    $this->drupalLogin($web_user);
  }

  public function testMenuLinkDelete() {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $element = $this->xpath('//select[@id = :id]/option[@selected]', array(':id' => 'edit-menu-parent'));
    $this->assertTrue($element, 'A default menu parent was found.');
    $this->assertEqual('admin:', $element[0]['value'], '<Administration> menu is the parent.');

    $this->drupalPostForm(
      NULL,
      array(
        'title[0][value]' => t('Front page'),
        'link[0][uri]' => '<front>',
      ),
      t('Save')
    );
    $this->assertText(t('The menu link has been saved.'));
    $this->drupalGet('admin/structure/menu/manage/admin');
    $this->assertLink(t('Front page'));
    $this->drupalGet('admin/structure/menu/item/1/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/structure/menu/manage/admin');
    $this->assertNoLink(t('Front page'));
    $entity = entity_load('menu_link_content', 1);
    $this->assertNull($entity, 'Deleted menu link was not loaded.');
    $deleted_entity = entity_load_deleted('menu_link_content', 1);
    $this->assertNotNull($deleted_entity, 'Deleted menu link was loaded as deleted.');
  }

}
