<?php

namespace Drupal\media_instagram\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\media_instagram\InstagramAuthenticationInterface;
use Drupal\media_instagram\InstagramFetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Media Instagram routes.
 */
class MediaInstagramController extends ControllerBase {

  /**
   * The Instagram authentication management service.
   *
   * @var \Drupal\media_instagram\InstagramAuthenticationInterface
   */
  protected $instagramAuthentication;

  /**
   * Instagram fetcher service.
   *
   * @var \Drupal\media_instagram\InstagramFetcherInterface
   */
  protected $instagramFetcher;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a MediaInstagramController instance.
   *
   * @param \Drupal\media_instagram\InstagramAuthenticationInterface $instagram_authentication
   *   The Instagram authentication management service.
   * @param \Drupal\media_instagram\InstagramFetcherInterface $instagram_fetcher
   *   The Instagram fetcher service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(InstagramAuthenticationInterface $instagram_authentication, InstagramFetcherInterface $instagram_fetcher, TimeInterface $time) {
    $this->instagramAuthentication = $instagram_authentication;
    $this->instagramFetcher = $instagram_fetcher;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_instagram.instagram_authentication'),
      $container->get('media_instagram.instagram_fetcher'),
      $container->get('datetime.time')
    );
  }

  /**
   * Builds the page for linking a Instagram account.
   */
  public function link() {
    $auth = $this->config('media_instagram.settings')->get('authentication');

    if (empty($auth['app_id']) || empty($auth['app_secret'])) {
      return [
        '#markup' => '<p>' . $this->t('Instagram account linking has not been set up yet for this website. Please contact your website administrator.') . '</p>',
      ];
    }

    return [
      'content' => [
        '#markup' => '<p>' . $this->t("Only one Instagram account's media can be pulled in by the website. Please use the link below to get start the process of linking this website with the Instagram account.") . '</p>',
      ],
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Link with Instagram'),
        '#url' => $this->instagramAuthentication->createLoginLink($auth['app_id']),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],
    ];
  }

  /**
   * Redirect page after Instagram login.
   */
  public function afterLogin(Request $request) {
    $query = $request->query;

    if ($query->get('error_reason') == 'user_denied') {
      return ['#markup' => '<p>' . $this->t('Login cancelled.') . '</p>'];
    }

    $link = $this->t('<a href=":href">Please try again.</a>', [
      ':href' => Url::fromRoute('media_instagram.link')->toString(),
    ]);

    if ($query->has('error')) {
      $this->getLogger('media_instagram')->error('Instagram login error: <pre><code>@parameters</code></pre>', [
        '@parameters' => json_encode($query->all(), JSON_PRETTY_PRINT),
      ]);
      return ['#markup' => '<p>' . $this->t('An error occurred.') . " $link</p>"];
    }

    if (!$this->instagramAuthentication->isValidState($query->get('state'))) {
      return ['#markup' => '<p>' . $this->t('Login expired.') . " $link</p>"];
    }

    if (!$token = $this->instagramFetcher->getAccessToken($query->get('code'))) {
      return ['#markup' => '<p>' . $this->t('An error occurred linking the Instagram account.') . " $link</p>"];
    }

    $this->state()->set('media_instagram.token', [
      'token' => $token['access_token'],
      // Set refresh timestamp, ¾ before token expires to give a time buffer
      // in case a refresh request fails, thus giving more opportunities in
      // future cron runs.
      'refresh' => $this->time->getRequestTime() + floor($token['expires_in'] * 0.75),
    ]);

    return ['#markup' => '<p>' . $this->t('Linking successful.') . '</p>'];
  }

}
