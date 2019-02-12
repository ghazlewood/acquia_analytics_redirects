<?php

namespace Drupal\acquia_analytics_redirects\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\UrlHelper;

/**
 * Subscriber for appending Stripped Query String back on to redirect URL.
 */
class AnalyticsRedirectsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function getHeaderAcquiaStrippedQuery(FilterResponseEvent $event) {

    /* @var $response \Symfony\Component\HttpFoundation\RedirectResponse */
    $response = $event->getResponse();

    if ($response->getStatusCode() == 301 || $response->getStatusCode() == 302) {
      $request = $event->getRequest();
      $headers = $request->headers;

      if (!empty($query_string = $headers->get('X-Acquia-Stripped-Query'))) {
        $target = $response->getTargetUrl();
        $url_parts = UrlHelper::parse($target);
        $target = $url_parts['path'] . '?' . $query_string;
        
        // Make sure unique values of X-Acquia-Stripped-Query are stored in
        // different cache variations in Acquia Varnish.
        $response->headers->set('Vary', 'X-Acquia-Stripped-Query', FALSE);
        $response->headers->set('X-Acquia-Stripped-Query', 'True');
        
        $event->setResponse(new TrustedRedirectResponse($target, $response->getStatusCode(), $response->headers->all()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Response: set redirect destination if applicable.
    $events[KernelEvents::RESPONSE][] = ['getHeaderAcquiaStrippedQuery', -1024];
    return $events;
  }

}
