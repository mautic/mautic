<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_ON_BUILD => array('onStageBuild', 0),
            PageEvents::PAGE_ON_HIT     => array('onPageHit', 0)
        );
    }

    /**
     * @param StageBuilderEvent $event
     */
    public function onStageBuild(StageBuilderEvent $event)
    {
        $action = array(
            'group'       => 'mautic.page.stage.action',
            'label'       => 'mautic.page.stage.action.pagehit',
            'description' => 'mautic.page.stage.action.pagehit_descr',
            'callback'    => array('\\Mautic\\PageBundle\\Helper\\StageActionHelper', 'validatePageHit'),
            'formType'    => 'stageaction_pagehit'
        );

        $event->addAction('page.hit', $action);

        $action = array(
            'group'       => 'mautic.page.stage.action',
            'label'       => 'mautic.page.stage.action.urlhit',
            'description' => 'mautic.page.stage.action.urlhit_descr',
            'callback'    => array('\\Mautic\\PageBundle\\Helper\\StageActionHelper', 'validateUrlHit'),
            'formType'    => 'stageaction_urlhit',
            'formTheme'   => 'MauticPageBundle:FormTheme\Stage'
        );

        $event->addAction('url.hit', $action);
    }

    /**
     * Trigger stage actions for page hits
     *
     * @param Events\PageHitEvent $event
     */
    public function onPageHit(Events\PageHitEvent $event)
    {
        if ($event->getPage()) {
            // Mautic Landing Page was hit
            $this->factory->getModel('stage')->triggerAction('page.hit', $event->getHit());
        } else {
            // Mautic Tracking Pixel was hit
            $this->factory->getModel('stage')->triggerAction('url.hit', $event->getHit());
        }
    }
}
