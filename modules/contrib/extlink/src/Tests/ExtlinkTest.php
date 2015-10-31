<?php
/**
 * @file
 * Contains Drupal\extlink\Tests\ExtlinkTest.
 */
namespace Drupal\extlink\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Testing the basic functionality of External Links
 *
 * @group Extlink
 *
 */
class ExtlinkTest extends ExtlinkTestBase {
	public function testExtlinkOnFrontPage() {
		//Get main page
		$this->drupalGet('');
	}
}