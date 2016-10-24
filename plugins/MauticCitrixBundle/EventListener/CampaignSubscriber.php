<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;

/**
 * Class CampaignSubscriber
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            CitrixEvents::ON_CITRIX_WEBINAR_EVENT => array('onWebinarEvent', 0),
            CitrixEvents::ON_CITRIX_MEETING_EVENT => array('onMeetingEvent', 0),
            CitrixEvents::ON_CITRIX_TRAINING_EVENT => array('onTrainingEvent', 0),
            CitrixEvents::ON_CITRIX_ASSIST_EVENT => array('onAssistEvent', 0),
        );
    }

    public static function onMeetingEvent(CampaignExecutionEvent $event)
    {
        return self::onCitrixEvent(CitrixProducts::GOTOMEETING, $event);
    }

    public static function onTrainingEvent(CampaignExecutionEvent $event)
    {
        return self::onCitrixEvent(CitrixProducts::GOTOTRAINING, $event);
    }

    public static function onAssistEvent(CampaignExecutionEvent $event)
    {
        return self::onCitrixEvent(CitrixProducts::GOTOASSIST, $event);
    }

    public static function onWebinarEvent(CampaignExecutionEvent $event)
    {
        return self::onCitrixEvent(CitrixProducts::GOTOWEBINAR, $event);
    }

    /**
     * @param string $product
     * @param CampaignExecutionEvent $event
     * @return bool
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public static function onCitrixEvent($product, CampaignExecutionEvent $event)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return false;
        }
        /** @var CitrixModel $citrixModel */
        $citrixModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel('citrix');
        $config = $event->getConfig();
        $criteria = $config['event-criteria-'.$product];
        $list = $config[$product.'-list'];
        $isAny = in_array('ANY', $list, true);
        $email = $event->getLead()->getEmail();

        if ('registeredToAtLeast' === $criteria) {
            $counter = $citrixModel->countEventsBy(
                $product,
                $email,
                CitrixEventTypes::REGISTERED,
                $isAny ? [] : $list
            );
        } else {
            if ('attendedToAtLeast' === $criteria) {
                $counter = $citrixModel->countEventsBy(
                    $product,
                    $email,
                    CitrixEventTypes::ATTENDED,
                    $isAny ? [] : $list
                );
            } else {
                return false;
            }
        }

        return ($counter > 0);
    }

    /**
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $activeProducts = [];

        if (CitrixHelper::isAuthorized('Gotowebinar')) {
            $activeProducts[] = CitrixProducts::GOTOWEBINAR;
        }

        if (CitrixHelper::isAuthorized('Gotomeeting')) {
            $activeProducts[] = CitrixProducts::GOTOMEETING;
        }

        if (CitrixHelper::isAuthorized('Gototraining')) {
            $activeProducts[] = CitrixProducts::GOTOTRAINING;
        }

        if (CitrixHelper::isAuthorized('Gotoassist')) {
            $activeProducts[] = CitrixProducts::GOTOASSIST;
        }

        if (0 === count($activeProducts)) {
            return;
        }

        $eventNames = [
            CitrixProducts::GOTOWEBINAR => CitrixEvents::ON_CITRIX_WEBINAR_EVENT,
            CitrixProducts::GOTOMEETING => CitrixEvents::ON_CITRIX_MEETING_EVENT,
            CitrixProducts::GOTOTRAINING => CitrixEvents::ON_CITRIX_TRAINING_EVENT,
            CitrixProducts::GOTOASSIST => CitrixEvents::ON_CITRIX_ASSIST_EVENT,
        ];

        foreach ($activeProducts as $product) {
            $event->addDecision(
                'citrix.event.'.$product,
                array(
                    'label' => 'plugin.citrix.campaign.event.'.$product.'.label',
                    'formType' => 'citrix_campaign_event',
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product' => $product,
                        ],
                    ],
                    'eventName' => $eventNames[$product],
                )
            );
        }
    }

}
