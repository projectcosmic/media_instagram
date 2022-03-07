<?php

namespace Drupal\media_instagram;

/**
 * Describes an Instagram fetcher service.
 */
interface InstagramFetcherInterface {

  /**
   * Gets an Instagram post from the Basic Display API.
   *
   * @param string $token
   *   An access token.
   * @param string $id
   *   The ID of the post to get.
   *
   * @return string[]|null
   *   Returns NULL if there was an error or an array of data, which may
   *   includes:
   *   - caption: The post's caption text.
   *   - id: The post's ID.
   *   - media_url: The post's image URL.
   *   - permalink: The post's permanent URL. Will be omitted if the post
   *     contains copyrighted material, or has been flagged for a copyright
   *     violation.
   *   - thumbnail_url: The Media's thumbnail image URL for videos only.
   *   - timestamp: The post's publish date in ISO 8601 format.
   *   - username: The post owner's username.
   */
  public function getPost($token, $id);

  /**
   * Gets posts for an Instagram user from its access token.
   *
   * @param string $token
   *   An access token.
   *
   * @return string[]|null
   *   Returns NULL if there was an error or an array of data, which may
   *   includes:
   *   - id: The post's ID.
   *   - timestamp: The post's publish date in ISO 8601 format.
   */
  public function getUserPosts($token);

  /**
   * Gets a long-lived access token from an OAuth code.
   *
   * @param string $code
   *   The code.
   *
   * @return array|null
   *   NULL if an error occurred or the access token information including:
   *   - access_token: The long-lived access token.
   *   - token_type: The token type, will be "bearer".
   *   - expires_in: The number of seconds before the token expires.
   */
  public function getAccessToken($code);

  /**
   * Refreshes an access token.
   *
   * @param string $token
   *   The access token to refresh.
   *
   * @return array|null
   *   NULL if an error occurred or the access token information including:
   *   - access_token: The long-lived access token.
   *   - token_type: The token type, will be "bearer".
   *   - expires_in: The number of seconds before the token expires.
   */
  public function refreshToken($token);

}
