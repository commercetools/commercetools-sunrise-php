<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Service;

use Pimple\Container;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieSessionServiceProvider extends SessionServiceProvider
{
    public function onKernelResponse(FilterResponseEvent $event) {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $session = $event->getRequest()->getSession();
        $session->save();
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'), 0);
        parent::subscribe($app, $dispatcher);
    }
}
