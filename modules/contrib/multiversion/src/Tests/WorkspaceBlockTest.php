<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceBlockTest.
 */

namespace Drupal\multiversion\Tests;

/**
 * Tests workspace block functionality.
 *
 * @group multiversion
 */
class WorkspaceBlockTest extends MultiversionWebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * A web user.
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(array(
      'administer blocks',
      'create article content',
      'access administration pages',
      'access content',
    ));
    $this->drupalLogin($this->webUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  public function testBlock() {
    $this->drupalPlaceBlock('multiversion_workspace_block', array('region' => 'sidebar_first', 'label' => 'Workspace switcher'));
    $this->drupalGet('');

    // Confirm that the block is being displayed.
    $this->assertText('Workspace switcher', t('Block successfully being displayed on the page.'));
    $front = \Drupal::url('<front>');
    $this->assertRaw('href="'. $front .'"', 'The id of the default workspace was displayed in the Workspace switcher block as a link.');
    $id = $this->randomMachineName();
    $entity = entity_create('workspace', array('id' => $id));
    $entity->save();
    $node = entity_create('node', array('type' => 'article', 'title' => 'Test article'));
    $node->save();
    $nid = $node->id();
    drupal_flush_all_caches();
    $this->drupalGet('');
    $this->assertText('Test article', 'The title of the test article was displayed on the front page.');
    $this->drupalGet("node/$nid");
    $this->assertText('Test article');
    $this->drupalGet('<front>');
    $url = $front . "?workspace=$id";
    $this->assertRaw('href="'. $url .'"', 'The id of the new workspace was displayed in the Workspace switcher block as a link.');
    $this->drupalGet("/node/$nid", array('query' => array('workspace' => $id)));
    $this->assertText('Page not found');
    $this->drupalGet('<front>', array('query' => array('workspace' => 'default')));
    $this->assertText('Test article', 'The title of the test article was displayed on the front page.');
    $this->drupalGet('<front>', array('query' => array('workspace' => $id)));
    $this->drupalGet('/node/add/article');
    $this->assertText('Create Article');
    $this->drupalGet('<front>', array('query' => array('workspace' => $id)));
    $this->assertNoText('Test article', 'The title of the test article was not displayed on the front page after switching the workspace.');
    $entity->delete();
    drupal_flush_all_caches();
    $this->drupalGet('');
    $this->assertNoText($id, 'The id of the deleted workspace was not displayed in the Workspace switcher block.');
  }

}
