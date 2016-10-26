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
use MauticPlugin\MauticCitrixBundle\Helper\CitrixRegistrationTrait;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;

/**
 * Class CampaignSubscriber
 */
class CampaignSubscriber extends CommonSubscriber
{

    use CitrixRegistrationTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            CitrixEvents::ON_CITRIX_WEBINAR_EVENT => ['onWebinarEvent', 0],
            CitrixEvents::ON_CITRIX_MEETING_EVENT => ['onMeetingEvent', 0],
            CitrixEvents::ON_CITRIX_TRAINING_EVENT => ['onTrainingEvent', 0],
            CitrixEvents::ON_CITRIX_ASSIST_EVENT => ['onAssistEvent', 0],
            CitrixEvents::ON_CITRIX_WEBINAR_ACTION => ['onWebinarAction', 0],
            CitrixEvents::ON_CITRIX_MEETING_ACTION => ['onMeetingAction', 0],
            CitrixEvents::ON_CITRIX_TRAINING_ACTION => ['onTrainingAction', 0],
            CitrixEvents::ON_CITRIX_ASSIST_ACTION => ['onAssistAction', 0],
        );
    }

    /* Actions */

    public function onWebinarAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(CitrixProducts::GOTOWEBINAR, $event));
    }

    public function onMeetingAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(CitrixProducts::GOTOMEETING, $event));
    }

    public function onTrainingAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(CitrixProducts::GOTOTRAINING, $event));
    }

    public function onAssistAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(CitrixProducts::GOTOASSIST, $event));
    }

    /**
     * @param string $product
     * @param CampaignExecutionEvent $event
     * @return bool
     */
    public function onCitrixAction($product, CampaignExecutionEvent $event)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return false;
        }

        // get firstName, lastName and email from keys for sender email
        $config = $event->getConfig();
        $criteria = $config['event-criteria-'.$product];
        /** @var array $list */
        $list = $config[$product.'-list'];

        if (in_array($criteria, ['webinar_register', 'training_register'], true)) {
            try {
                $productlist = CitrixHelper::getCitrixChoices($product);
                $products = [];

                foreach ($list as $productId) {
                    if (array_key_exists(
                        $productId,
                        $productlist
                    )) {
                        $products[] = array(
                            'productId' => $productId,
                            'productTitle' => $productlist[$productId],
                        );
                    }
                }
                self::registerProduct($product, $event->getLead(), $products);
            } catch (\Exception $ex) {
                CitrixHelper::log('onCitrixAction - '.$product.': '.$ex->getMessage());
            }
        }

        /*
         * $config = Array (     
         *   [event-criteria-training] => training_start     
         *   [training-list] => Array         
         *     (             
         *       [0] => 3803653102383157249        
         *      )  
         *   ) 
         * 
         * 'webinar_register' 
            'meeting_start' 
            'training_register' 
            'training_start' 
            'assist_screensharing'
         */


        return true;
    }

    /* Events */

    public function onWebinarEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(CitrixProducts::GOTOWEBINAR, $event));
    }

    public function onMeetingEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(CitrixProducts::GOTOMEETING, $event));
    }

    public function onTrainingEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(CitrixProducts::GOTOTRAINING, $event));
    }

    public function onAssistEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(CitrixProducts::GOTOASSIST, $event));
    }

    /**
     * @param string $product
     * @param CampaignExecutionEvent $event
     * @return bool
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function onCitrixEvent($product, CampaignExecutionEvent $event)
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
        foreach (CitrixProducts::toArray() as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
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

        $actionNames = [
            CitrixProducts::GOTOWEBINAR => CitrixEvents::ON_CITRIX_WEBINAR_ACTION,
            CitrixProducts::GOTOMEETING => CitrixEvents::ON_CITRIX_MEETING_ACTION,
            CitrixProducts::GOTOTRAINING => CitrixEvents::ON_CITRIX_TRAINING_ACTION,
            CitrixProducts::GOTOASSIST => CitrixEvents::ON_CITRIX_ASSIST_ACTION,
        ];

        foreach ($activeProducts as $product) {
            $event->addCondition(
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

            $event->addAction(
                'citrix.action.'.$product,
                array(
                    'label' => 'plugin.citrix.campaign.action.'.$product.'.label',
                    'formType' => 'citrix_campaign_action',
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product' => $product,
                        ],
                    ],
                    'eventName' => $actionNames[$product],
                )
            );
        }
    }

}
