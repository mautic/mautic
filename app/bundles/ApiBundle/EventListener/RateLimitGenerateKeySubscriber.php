<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RateLimitGenerateKeySubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
          RateLimitEvents::GENERATE_KEY => ['onGenerateKey', 0],
        ];
    }

    public function onGenerateKey(GenerateKeyEvent $event)
    {
        $suffix = $this->coreParametersHelper->get('site_url');
        $event->addToKey($suffix);
    }
}
