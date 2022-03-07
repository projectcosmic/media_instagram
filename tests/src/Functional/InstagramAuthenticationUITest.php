<?php

namespace Drupal\Tests\media_instagram\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Instagram OAuth flow.
 *
 * @group media_instagram
 */
class InstagramAuthenticationUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_instagram',
    'media_instagram_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['media_instagram link instagram']));
  }

  /**
   * Tests authentication flow.
   */
  public function testAuthenticationFlow() {
    $this->drupalGet('/admin/media-instagram/link');

    // Show message when app configuration has not been set.
    $this->assertSession()->pageTextContains('Instagram account linking has not been set up');

    $this->config('media_instagram.settings')
      ->set('authentication.app_id', (string) random_int(0, 1000))
      ->set('authentication.app_secret', $this->randomMachineName())
      ->save();

    $this->drupalGet('/admin/media-instagram/link');
    $this->assertSession()->linkExists('Link with Instagram');

    $this->drupalGet('/admin/media-instagram/after-login', [
      'query' => [
        'error_reason' => 'user_denied',
        'error' => 'access_denied',
        'error_description' => 'Permissions error.',
      ],
    ]);
    $this->assertSession()->pageTextContains('Login cancelled.');

    $this->drupalGet('/admin/media-instagram/link');
    $this->drupalGet('/admin/media-instagram/after-login', [
      'query' => [
        'error_reason' => $this->randomMachineName(),
        'error' => $this->randomMachineName(),
        'error_description' => $this->randomMachineName(),
      ],
    ]);
    $this->assertSession()->pageTextContains('An error occurred');

    \Drupal::state()->set('media_instagram_test.success', TRUE);
    $this->drupalGet('/admin/media-instagram/link');
    $this->drupalGet('/admin/media-instagram/after-login', [
      'query' => [
        'state' => \Drupal::service('tempstore.private')
          ->get('media_instagram')
          ->get('login_state'),
        'code' => $this->randomMachineName(),
      ],
    ]);
    $this->assertSession()->pageTextContains('Linking successful.');

    $state_data = \Drupal::state()->get('media_instagram.token');
    $this->assertNotNull($state_data, 'Token data saved.');
    $this->assertNotNull($state_data['token'], 'Token saved.');

    \Drupal::state()->set('media_instagram_test.success', FALSE);
    $this->drupalGet('/admin/media-instagram/link');
    $this->drupalGet('/admin/media-instagram/after-login', [
      'query' => [
        'state' => \Drupal::service('tempstore.private')
          ->get('media_instagram')
          ->get('login_state'),
        'code' => $this->randomMachineName(),
      ],
    ]);
    $this->assertSession()->pageTextContains('An error occurred linking the Instagram account.');
  }

}
