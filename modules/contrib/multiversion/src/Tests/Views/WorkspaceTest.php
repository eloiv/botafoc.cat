<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\Views\WorkspaceTest.
 */

namespace Drupal\multiversion\Tests\Views;

use Drupal\multiversion\Entity\Workspace;

/**
 * Tests the workspace and current_workspace field handlers.
 *
 * @group multiversion
 * @see \Drupal\multiversion\Plugin\views\filter\CurrentWorkspace
 */
class WorkspaceTest extends MultiversionTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_current_workspace'];

  /**
   * Tests the workspace filter.
   */
  public function testWorkspace() {
    $admin_user = $this->drupalCreateUser(['bypass node access']);
    $uid = $admin_user->id();
    $this->drupalLogin($admin_user);

    // Create two nodes on 'default' workspace.
    $node1 = $this->drupalCreateNode(['uid' => $uid]);
    $node2 = $this->drupalCreateNode(['uid' => $uid]);

    // Create a new workspace and switch to it.
    $new_workspace = Workspace::create(['id' => 'new_workspace']);
    $new_workspace->save();
    \Drupal::service('workspace.manager')->setActiveWorkspace($new_workspace);

    // Create two nodes on 'new_workspace' workspace.
    $node3 = $this->drupalCreateNode(['uid' => $uid]);
    $node4 = $this->drupalCreateNode(['uid' => $uid]);

    // Test current_workspace filter.
    $this->drupalGet('test_current_workspace', ['query' => ['workspace' => 'new_workspace']]);
    $this->assertNoText($node1->label());
    $this->assertNoText($node2->label());
    $this->assertText($node3->label());
    $this->assertText($node4->label());

    $this->drupalGet('test_current_workspace', ['query' => ['workspace' => 'default']]);
    $this->assertText($node1->label());
    $this->assertText($node2->label());
    $this->assertNoText($node3->label());
    $this->assertNoText($node4->label());
  }

}
