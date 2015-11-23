<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceBlockTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests workspace block functionality.
 *
 * @group multiversion
 */
class WorkspaceBlockTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['multiversion'];

  /**
   * A web user.
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser([
      'administer blocks',
      'create article content',
      'access administration pages',
      'access content',
    ]);
    $this->drupalLogin($this->webUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  public function testBlock() {
    $this->drupalPlaceBlock('multiversion_workspace_block', ['region' => 'sidebar_first', 'label' => 'Workspace switcher']);
    $this->drupalGet('');

    // Confirm that the block is being displayed.
    $this->assertText('Workspace switcher', t('Block successfully being displayed on the page.'));
    $front = Url::fromRoute('<front>')->toString(TRUE)->getGeneratedUrl();
    $this->assertRaw('href="'. $front .'"', 'The id of the default workspace was displayed in the Workspace switcher block as a link.');
    $id = $this->randomMachineName();
    $entity = Workspace::create(['id' => $id]);
    $entity->save();
    $node = Node::create(['type' => 'article', 'title' => 'Test article']);
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
    $this->drupalGet("/node/$nid", ['query' => ['workspace' => $id]]);
    $this->assertText('Page not found');
    $this->drupalGet('<front>', ['query' =>['workspace' => 'default']]);
    $this->assertText('Test article', 'The title of the test article was displayed on the front page.');
    $this->drupalGet('<front>', ['query' => ['workspace' => $id]]);
    $this->drupalGet('/node/add/article');
    $this->assertText('Create Article');
    $this->drupalGet('<front>', ['query' => ['workspace' => $id]]);
    $this->assertNoText('Test article', 'The title of the test article was not displayed on the front page after switching the workspace.');
    $entity->delete();
    drupal_flush_all_caches();
    $this->drupalGet('');
    $this->assertNoText($id, 'The id of the deleted workspace was not displayed in the Workspace switcher block.');
  }

}
