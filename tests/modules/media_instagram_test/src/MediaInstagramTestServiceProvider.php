<?php

namespace Drupal\media_instagram_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters services for Media Instagram tests.
 */
class MediaInstagramTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('media_instagram.instagram_fetcher')
      ->setArgument(0, new Reference('media_instagram_test.http_client'));
  }

}
