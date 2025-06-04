<?php

namespace Drupal\Tests\acquia_analytics_redirects\Kernel;

use Drupal\acquia_analytics_redirects\EventSubscriber\AnalyticsRedirectsEventSubscriber;
use Drupal\Component\Utility\UrlHelper;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Kernel tests for the redirect subscriber.
 *
 * @group acquia_analytics_redirects
 */
class AnalyticsRedirectsEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_analytics_redirects',
  ];

  /**
   * The subject under test.
   *
   * @var \Drupal\acquia_analytics_redirects\EventSubscriber\AnalyticsRedirectsEventSubscriber|null
   */
  protected ?AnalyticsRedirectsEventSubscriber $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->subscriber = $this->container->get('acquia_analytics_redirects_subscriber');
  }

  /**
   * Test case for a non-3XX response.
   */
  public function testNon3xxRequest() {
    $request = new Request();
    $response = new Response('', Response::HTTP_OK);
    $event = new ResponseEvent($this->container->get('http_kernel'), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $this->subscriber->getHeaderAcquiaStrippedQuery($event);
    static::assertSame($response, $event->getResponse());
    static::assertEmpty($response->getVary());
  }

  /**
   * Test case for a 3XX response without special request header.
   */
  public function testNoAcquiaStrippedQueryRequestHeader() {
    $request = new Request();
    $response = new RedirectResponse('https://example.com', Response::HTTP_MOVED_PERMANENTLY);
    $event = new ResponseEvent($this->container->get('http_kernel'), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $this->subscriber->getHeaderAcquiaStrippedQuery($event);
    static::assertSame($response, $event->getResponse());
    static::assertEmpty($response->getVary());
  }

  /**
   * Test case for a 3XX response with special request header.
   */
  public function testHasAcquiaStrippedQueryRequestHeader() {
    $request = new Request();

    // Let's pretend to be Varnish.
    $request->headers->set('X-Acquia-Stripped-Query', 'gclid=4');

    $response = new RedirectResponse('https://example.com', Response::HTTP_MOVED_PERMANENTLY);
    $event = new ResponseEvent($this->container->get('http_kernel'), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $this->subscriber->getHeaderAcquiaStrippedQuery($event);

    // The response should be replaced with a new one.
    static::assertNotSame($response, $event->getResponse());

    // The response should vary on the stripped query header.
    static::assertSame(['X-Acquia-Stripped-Query'], $response->getVary());

    // The response should have the proper query parameter.
    static::assertSame(
      ['gclid' => '4'],
      UrlHelper::parse($event->getResponse()->getTargetUrl())['query']
    );
  }

}
