<?php

namespace Drupal\media_instagram;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;

/**
 * Instagram authentication management service.
 */
class InstagramAuthentication implements InstagramAuthenticationInterface {

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a InstagramAuthentication instance.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, UuidInterface $uuid_service) {
    $this->tempStore = $temp_store_factory->get('media_instagram');
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public function createLoginLink($app_id) {
    $state = $this->uuidService->generate();
    $this->tempStore->set('login_state', $state);

    return Url::fromUri('https://api.instagram.com/oauth/authorize', [
      'query' => [
        'client_id' => $app_id,
        'redirect_uri' => Url::fromRoute('media_instagram.after_login', [], ['absolute' => TRUE])->toString(),
        'state' => $state,
        'scope' => 'user_profile,user_media',
        'response_type' => 'code',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function isValidState($state) {
    if ($this->tempStore->get('login_state') == $state) {
      $this->tempStore->delete('login_state');
      return TRUE;
    }

    return FALSE;
  }

}
