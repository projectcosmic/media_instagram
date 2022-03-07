<?php

namespace Drupal\media_instagram;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

/**
 * Instagram fetcher service.
 */
class InstagramFetcher implements InstagramFetcherInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config object for Instagram post settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs the Instagram fetcher service.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->config = $config_factory->get('media_instagram.settings');
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getPost($token, $id) {
    $cache_id = "media:instagram:$id";

    $cached = $this->cacheBackend->get($cache_id);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->get("https://graph.instagram.com/v13.0/$id", [
        'query' => [
          'fields' => 'caption,id,media_url,permalink,thumbnail_url,timestamp,username',
          'access_token' => $token,
        ],
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $this->cacheBackend->set($cache_id, $data);
      return $data;
    }
    catch (TransferException $e) {
      $this->logger->error($e->__toString());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserPosts($token) {
    try {
      $response = $this->httpClient->get('https://graph.instagram.com/v13.0/me/media', [
        'query' => [
          'fields' => 'caption,id,media_url,permalink,thumbnail_url,timestamp,username',
          'access_token' => $token,
        ],
      ]);

      $posts = json_decode((string) $response->getBody(), TRUE)['data'] ?? [];
      // Save posts that would need to be fetched by ::getPost() to cache to
      // reduce usage.
      foreach ($posts as $post) {
        $cache_id = "media:instagram:$post[id]";
        $this->cacheBackend->set($cache_id, $post);
      }
      return $posts;
    }
    catch (TransferException $e) {
      $this->logger->error($e->__toString());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($code) {
    try {
      $response = $this->httpClient->post('https://api.instagram.com/oauth/access_token', [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'client_id' => $this->config->get('authentication.app_id'),
          'client_secret' => $this->config->get('authentication.app_secret'),
          'redirect_uri' => Url::fromRoute('media_instagram.after_login', [], ['absolute' => TRUE])->toString(),
          'code' => $code,
        ],
      ]);

      $response = $this->httpClient->get('https://graph.instagram.com/access_token', [
        'query' => [
          'grant_type' => 'ig_exchange_token',
          'client_secret' => $this->config->get('authentication.app_secret'),
          'access_token' => json_decode((string) $response->getBody())->access_token,
        ],
      ]);

      return json_decode((string) $response->getBody(), TRUE);
    }
    catch (TransferException $e) {
      $this->logger->error($e->__toString());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshToken($token) {
    try {
      $response = $this->httpClient->get('https://graph.instagram.com/refresh_access_token', [
        'query' => [
          'grant_type' => 'ig_refresh_token',
          'access_token' => $token,
        ],
      ]);

      return json_decode((string) $response->getBody(), TRUE);
    }
    catch (TransferException $e) {
      $this->logger->error($e->__toString());
    }

    return NULL;
  }

}
