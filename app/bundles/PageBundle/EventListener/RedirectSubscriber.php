<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event\RedirectEvent;
use Mautic\PageBundle\Helper\RedirectHelper;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectSubscriber implements EventSubscriberInterface
{
    private RedirectHelper $redirectHelper;

    public function __construct(RedirectHelper $redirectHelper)
    {
        $this->redirectHelper = $redirectHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::ON_REDIRECT => ['onRedirect', 0],
        ];
    }

    public function onRedirect(RedirectEvent $redirectEvent)
    {
        $redirectResponse = $this->redirectHelper->internalRedirect($redirectEvent->getRedirect());
        $redirectEvent->setRedirectResponse($redirectResponse);
    }
}
