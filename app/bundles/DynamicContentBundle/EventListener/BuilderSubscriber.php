<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

class BuilderSubscriber extends CommonSubscriber
{
    /**
     * @var DynamicContentHelper
     */
    protected $dynamicContentHelper;

    /**
     * BuilderSubscriber constructor.
     *
     * @param MauticFactory        $factory
     * @param DynamicContentHelper $dynamicContentHelper
     */
    public function __construct(MauticFactory $factory, DynamicContentHelper $dynamicContentHelper)
    {
        parent::__construct($factory);

        $this->dynamicContentHelper = $dynamicContentHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_SEND => ['onEmailGenerate', 0]
        ];
    }

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $event->addToken('{dynamiccontent=slot_name}', $this->translator->trans('mautic.dynamicContent.dynamicContent'));
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content = $this->dynamicContentHelper->replaceTokensInContent($event->getContent(), $event->getLead());

        $event->setContent($content);
    }
}
