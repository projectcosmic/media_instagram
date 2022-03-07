<?php

namespace Drupal\Tests\media_instagram\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\State\StateInterface;
use Drupal\media_instagram\InstagramFetcher;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\media_instagram\InstagramFetcher
 * @group media_instagram
 */
class InstagramFetcherTest extends UnitTestCase {

  /**
   * @covers ::getPost()
   */
  public function testGetPost() {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('error');
    $cache_backend = $this->createMock(CacheBackendInterface::class);

    $mock = new MockHandler([
      new RequestException('Error', new Request('GET', 'https://graph.instagram.com/12.0/__POST_ID__')),
      new Response(200, [], '{"message":"Success Response"}'),
    ]);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    $fetcher = new InstagramFetcher($client, $logger, $this->getConfigFactoryStub(), $cache_backend);
    $this->assertNull($fetcher->getPost($this->randomMachineName(), $this->randomMachineName()), 'Request exception results in NULL return value.');
    $this->assertIsArray($fetcher->getPost($this->randomMachineName(), $this->randomMachineName()), 'Returns array of data.');
  }

  /**
   * @covers ::getUserPosts()
   * @dataProvider getUserPostsProvider
   */
  public function testGetUserPosts($mock_handler, $expected = NULL) {
    $client = new Client(['handler' => HandlerStack::create($mock_handler)]);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($expected === NULL ? $this->once() : $this->never())->method('error');
    $config_factory = $this->getConfigFactoryStub(['media_instagram.settings' => []]);
    $cache_backend = $this->createMock(CacheBackendInterface::class);

    $fetcher = new InstagramFetcher($client, $logger, $config_factory, $cache_backend);
    $assert = is_int($expected) ? 'assertCount' : 'assertEquals';
    $this->{$assert}($expected, $fetcher->getUserPosts($this->randomMachineName()));
  }

  /**
   * Provides test cases for ::testGetUserPosts().
   */
  public function getUserPostsProvider() {
    $page_id = random_int(10000, PHP_INT_MAX);

    return [
      'Request error' => [
        new MockHandler([
          new RequestException('Error', new Request('GET', 'https://graph.instagram.com/v13.0/me/media')),
        ]),
      ],
      'Successful' => [
        new MockHandler([
          new Response(200, [], json_encode([
            'id' => (string) $page_id,
            'data' => [
              [
                'created_time' => date('c', 2000),
                'id' => "{$page_id}_1000",
              ],
              [
                'created_time' => date('c', 3000),
                'id' => "{$page_id}_1001",
              ],
            ],
          ])),
        ]),
        2,
      ],
    ];
  }

  /**
   * @covers ::getAccessToken()
   * @dataProvider getAccessTokenProvider
   */
  public function testGetAccessToken($mock_handler, $expect_error = TRUE, $expected = NULL) {
    $client = new Client(['handler' => HandlerStack::create($mock_handler)]);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($expect_error ? $this->once() : $this->never())->method('error');
    $config_factory = $this->getConfigFactoryStub(['media_instagram.settings' => []]);
    $cache_backend = $this->createMock(CacheBackendInterface::class);

    $urlGenerator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $container = new ContainerBuilder();
    $container->set('url_generator', $urlGenerator);
    \Drupal::setContainer($container);

    $fetcher = new InstagramFetcher($client, $logger, $config_factory, $cache_backend);

    if ($expected) {
      $this->assertEquals($expected, $fetcher->getAccessToken($this->randomMachineName())['access_token']);
    }
    else {
      $this->assertNull($fetcher->getAccessToken($this->randomMachineName()));
    }
  }

  /**
   * Provides test cases for ::testGetAccessToken().
   */
  public function getAccessTokenProvider() {
    $token = $this->randomMachineName();

    return [
      'Short-lived token error' => [
        new MockHandler([
          new RequestException('Error getting short-lived token', new Request('GET', 'https://api.instagram.com/oauth/access_token')),
        ]),
      ],
      'Long-lived token error' => [
        new MockHandler([
          new Response(200, [], json_encode([
            'access_token' => '__SHORT_LIVED_ACCESS_TOKEN__',
            'user_id' => (string) random_int(0, PHP_INT_MAX),
          ])),
          new RequestException('Error getting long-lived token', new Request('GET', 'https://graph.instagram.com/access_token')),
        ]),
      ],
      'Successful token exchange' => [
        new MockHandler([
          new Response(200, [], json_encode([
            'access_token' => '__SHORT_LIVED_ACCESS_TOKEN__',
            'user_id' => (string) random_int(0, PHP_INT_MAX),
          ])),
          new Response(200, [], json_encode([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 5183944,
          ])),
        ]),
        FALSE,
        $token,
      ],
    ];
  }

}
