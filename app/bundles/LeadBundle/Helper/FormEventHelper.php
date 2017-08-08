<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;

/**
 * Class FormEventHelper.
 */
class FormEventHelper
{
    /**
     * @param Lead          $lead
     * @param MauticFactory $factory
     * @param               $action
     * @param               $config
     * @param               $form
     */
    public static function changePoints(Lead $lead, MauticFactory $factory, $action, $config, $form)
    {
        $model = $factory->getModel('lead');

        //create a new points change event
        $event = new PointsChangeLog();
        $event->setType('form');
        $event->setEventName($form->getId().':'.$form->getName());
        $event->setActionName($action->getName());
        $event->setIpAddress($factory->getIpAddress());
        $event->setDateAdded(new \DateTime());

        $event->setLead($lead);

        $oldPoints = $lead->getPoints();

        /** CAPTIVEA.CORE START REPLACE **/
        // $lead->adjustPoints($config['points'], $config['operator']);
        // 
        // $newPoints = $lead->getPoints();
        // 
        // $event->setDelta($newPoints - $oldPoints);
        if(!empty($config['scoringCategory'])) {
            $scoringCategory = $factory->getEntityManager()->getRepository('MauticScoringBundle:ScoringCategory')->find($config['scoringCategory']);
            if(!empty($scoringCategory) && !$scoringCategory->getIsGlobalScore()) {
                $factory->getEntityManager()->getRepository('MauticScoringBundle:ScoringValue')->adjustPoints($lead, $scoringCategory, $config['points'], $config['operator']);
                $event->setEventName($form->getId().':'.$form->getName().' ('.$scoringCategory->getName().')');
                $event->setDelta($config['points']);
            } else {
                $lead->adjustPoints($config['points'], $config['operator']);
                $newPoints = $lead->getPoints();
                $event->setDelta($newPoints - $oldPoints);
            }
        } else {
            $lead->adjustPoints($config['points'], $config['operator']);
            $newPoints = $lead->getPoints();
            $event->setDelta($newPoints - $oldPoints);
        }
        /** CAPTIVEA.CORE END REPLACE **/
        
        $lead->addPointsChangeLog($event);

        $model->saveEntity($lead, false);
    }

    /**
     * @param $action
     * @param $factory
     */
    public static function changeLists($action, $factory)
    {
        $properties = $action->getProperties();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $lead       = $leadModel->getCurrentLead();
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        if (!empty($addTo)) {
            $leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $leadModel->removeFromLists($lead, $removeFrom);
        }
    }

    public static function scoreContactsCompanies($action, $factory, $config)
    {
        $properties = $action->getProperties();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead');
        $lead      = $leadModel->getCurrentLead();
        $score     = $properties['score'];

        if (!empty($score)) {
            /** CAPTIVEA.CORE START REPLACE **/
            //$lead->adjustPoints($config['points'], $config['operator']);
            if(!empty($config['scoringCategory'])) {
                $scoringCategory = $factory->getEntityManager()->getRepository('MauticScoringBundle:ScoringCategory')->find($config['scoringCategory']);
                if(!empty($scoringCategory) && !$scoringCategory->getIsGlobalScore()) {
                    $lead = $factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')->getEntityWithPrimaryCompany($lead);
                    $primaryCompany = $lead->getPrimaryCompany();// it's an array... or null. depends.
                    if(!empty($primaryCompany) && !empty($primaryCompany['id'])) {
                        $primaryCompanyObject = $factory->getEntityManager()->getRepository('MauticLeadBundle:Company')->find($primaryCompany['id']);
                        $factory->getEntityManager()->getRepository('MauticScoringBundle:ScoringCompanyValue')->adjustPoints($primaryCompanyObject, $scoringCategory, $score);
                    }
                } else {
                    $leadModel->scoreContactsCompany($lead, $score);
                }
            } else {
                $leadModel->scoreContactsCompany($lead, $score);
            }
            /** CAPTIVEA.CORE END REPLACE **/
        }
    }
}
