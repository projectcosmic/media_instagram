<?php

namespace Drupal\Tests\media_instagram\Kernel;

use Drupal\media\Entity\Media;
use Drupal\media_instagram\InstagramFetcherInterface;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests periodical fetching of Instagram page posts.
 *
 * @group media_instagram
 */
class MediaInstagramFetchTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media', 'media_instagram'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('instagram', [
      'source_configuration' => [
        'fetch_count' => 2,
      ],
    ]);
  }

  /**
   * Tests cron run.
   */
  public function testCronFetching() {
    $state = $this->container->get('state');
    $state->set('media_instagram.token', [
      'token' => $this->randomMachineName(),
      'refresh' => PHP_INT_MAX,
    ]);

    $data = [
      [
        'id' => '10002',
        'timestamp' => date('c', strtotime('1st Jan 2022 01:00')),
      ],
      [
        'id' => '10001',
        'timestamp' => date('c', strtotime('1st Jan 2022 02:00')),
      ],
    ];
    $new = [
      'id' => '10003',
      'timestamp' => date('c', strtotime('1st Feb 2022 02:00')),
    ];

    $fetcher = $this->createMock(InstagramFetcherInterface::class);
    $fetcher
      ->method('getUserPosts')
      ->willReturnOnConsecutiveCalls(NULL, $data, [$new, ...$data]);
    $this->container->set('media_instagram.instagram_fetcher', $fetcher);

    $this->container->get('cron')->run();
    $this->assertCount(0, Media::loadMultiple(), 'Queue worker fails gracefully.');

    $this->container->get('cron')->run();
    $this->assertCount(2, Media::loadMultiple(), 'Media items are created.');

    $this->container->get('cron')->run();
    $this->assertCount(3, Media::loadMultiple(), 'Duplicate items are not created.');
  }

}
