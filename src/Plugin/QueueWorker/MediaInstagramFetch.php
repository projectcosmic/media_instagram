<?php

namespace Drupal\media_instagram\Plugin\QueueWorker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_instagram\InstagramFetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches new posts for am Instagram post media entity bundle.
 *
 * @QueueWorker(
 *   id = "media_instagram_fetch",
 *   title = @Translation("Instagram Fetch"),
 *   cron = { "time" = 60 },
 * )
 */
class MediaInstagramFetch extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The Instagram fetcher service.
   *
   * @var \Drupal\media_instagram\InstagramFetcherInterface
   */
  protected $instagramFetcher;

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Tests the test access block.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\media_instagram\InstagramFetcherInterface $instagram_fetcher
   *   The Instagram fetcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, InstagramFetcherInterface $instagram_fetcher, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->instagramFetcher = $instagram_fetcher;
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('media_instagram.instagram_fetcher'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof MediaTypeInterface) {
      $source = $data->getSource();
      $source_config = $source->getConfiguration();
      $token = $this->state->get('media_instagram.token');

      if ($source->getPluginId() == 'instagram' && $source_config['fetch_count'] > 0 && $token) {
        $bundle_key = $this->mediaStorage->getEntityType()->getKey('bundle');

        $state_key = "media_instagram.{$data->id()}.since";
        $since = $this->state->get($state_key, 0);

        $posts = $this->instagramFetcher->getUserPosts($token['token']);

        // If an error occurred or no posts, return early.
        if (empty($posts)) {
          return;
        }

        foreach (array_slice($posts, 0, $source_config['fetch_count']) as $post) {
          $timestamp = strtotime($post['timestamp']);

          // Rely on the post list being in descending published order so we
          // can exit out of the loop early if we encounter a post we should
          // have already seen.
          // @todo check that this post is not a duplicate due to the
          //   possibility of posts being added manually.
          if ($timestamp <= $since) {
            break;
          }

          $this->mediaStorage
            ->create([
              $bundle_key => $data->id(),
              $source_config['source_field'] => $post['id'],
            ])
            ->save();
        }

        $this->state->set($state_key, strtotime($posts[0]['timestamp']));
      }
    }
  }

}
