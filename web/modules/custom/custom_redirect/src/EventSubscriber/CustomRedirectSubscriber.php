<?php

namespace Drupal\custom_redirect\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\AdminContext;

class CustomRedirectSubscriber implements EventSubscriberInterface {

  protected AdminContext $adminContext;

  public function __construct(AdminContext $admin_context) {
    $this->adminContext = $admin_context;
  }

  /**
   * Redirect all user page views to /admin.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    if ($route_name === 'entity.user.canonical') {
      $event->setResponse(new RedirectResponse('/admin'));
    }
  }

  /**
   * Redirect after node add/edit to /admin.
   */
  /* public function onRespond(ResponseEvent $event): void {
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');

    // Esegui il redirect solo se non c'è già una destination.
    if (in_array($route_name, ['entity.node.edit_form', 'node.add']) && !$request->query->has('destination')) {
      $event->setResponse(new RedirectResponse('/admin'));
    }
  } */

  /**
   * Subscribed events.
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 0],
      //KernelEvents::RESPONSE => ['onRespond', -10],
    ];
  }

}
