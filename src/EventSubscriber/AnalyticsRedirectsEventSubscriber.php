<?php

namespace Drupal\acquia_analytics_redirects\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Subscriber for appending Stripped Query String back on to redirect URL.
 */
class AnalyticsRedirectsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function getHeaderAcquiaStrippedQuery(FilterResponseEvent $event) {

    $request = $event->getRequest();
    $headers = $request->headers;

    $response = $event->getResponse();

    if ($response->getStatusCode() == 301 || $response->getStatusCode() == 302) {

      if ($headers->has('X-Acquia-Stripped-Query')) {
        $target = $_ENV['SCRIPT_URI'] . '?' . $_ENV['HTTP_X_ACQUIA_STRIPPED_QUERY'];
        $event->setResponse(new RedirectResponse($target, $response->getStatusCode()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Response: set redirect destination if applicable.
    $events[KernelEvents::RESPONSE][] = ['getHeaderAcquiaStrippedQuery', 34];
    return $events;
  }

}
