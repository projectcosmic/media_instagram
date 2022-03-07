<?php

namespace Drupal\media_instagram;

/**
 * Describes an Instagram authentication management service.
 */
interface InstagramAuthenticationInterface {

  /**
   * Creates an Instagram Login link.
   *
   * @param string $app_id
   *   The Instagram App ID.
   *
   * @return \Drupal\Core\Url
   *   The Login link URL.
   */
  public function createLoginLink($app_id);

  /**
   * Checks that a state parameter from a redirect is valid.
   *
   * @param string $state
   *   The state string from the request URL.
   *
   * @return bool
   *   TRUE if the state was valid, FALSE otherwise.
   */
  public function isValidState($state);

}
