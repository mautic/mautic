<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\ScoreChangeLog;

/**
 * Class EventHelper
 *
 * @package Mautic\LeadBundle\Helper
 */
class EventHelper
{

    /**
     * @param       $action
     * @param       $form
     * @param array $post
     * @param array $server
     * @param       $factory
     * @param array $fields
     * @return array
     */
    public static function createLeadOnFormSubmit($action, $form, array $post, array $server, $factory, array $fields)
    {
        $model      = $factory->getModel('lead.lead');
        $em         = $factory->getEntityManager();
        $properties = $action->getProperties();

        //set the mapped data
        $leadFields = $factory->getModel('lead.field')->getEntities(
            array('filter' => array('isPublished' => true))
        );
        $data = array();
        foreach ($leadFields as $f) {
            $id    = $f->getId();
            $alias = $f->getAlias();
            $type  = $f->getType();

            $data[$alias] = '';

            if (!empty($properties['mappedFields'][$id])) {
                $mappedTo = $properties['mappedFields'][$id];
                if (isset($fields[$mappedTo])) {
                    $fieldName = $fields[$mappedTo]['alias'];
                    if (isset($post[$fieldName])) {
                        $value = is_array($post[$fieldName]) ? implode(', ', $post[$fieldName]) : $post[$fieldName];
                        $data[$alias] = $value;

                        //update the lead rather than creating a new one if there is for sure identifier match
                        if ($type == 'email') {
                            $leads = $em->getRepository('MauticLeadBundle:Lead')->getLeadsByFieldValue(
                                $alias,
                                $value
                            );
                            if (count($leads)) {
                                //there is a match so use the latest lead
                                $lead = $leads[0];
                            }
                        }
                    }
                }
            }
        }


        //check for existing IP address
        $ipAddress = $factory->getIpAddress($server['REMOTE_ADDR']);

        //no lead was found by a mapped email field so create a new one
        if (empty($lead)) {
            $lead = new Lead();
            $lead->setScore($properties['score']);
            $ipAddresses = false;

            //create a new score change event
            $lead->addScoreChangeLogEntry(
                'form',
                $form->getId() . ":" . $form->getName(),
                $action->getName(),
                $properties['score'],
                $ipAddress
            );
        } else {
            $ipAddresses = $lead->getIpAddresses();
        }

        //set the mapped fields
        $model->setFieldValues($lead, $data, false);

        //add the IP if the lead is not already associated with it
        if (!$ipAddresses || !$ipAddresses->contains($ipAddress)) {
            $lead->addIpAddress($ipAddress);
        }

        if (!empty($event)) {
            $event->setIpAddress($ipAddress);
            $lead->addScoreChangeLog($event);
        }

        //create a new lead
        $model->saveEntity($lead, false);

        //set the tracking cookies
        $model->setLeadCookie($lead->getId());

        //return the lead so it can be used elsewhere
        return array('lead' => $lead);
    }

    /**
     * @param array $post
     * @param array $server
     * @param       $fields
     * @param       $factory
     * @param       $action
     * @param       $form
     */
    public static function changeScoreOnFormSubmit(array $post, array $server,  $fields, $factory, $action, $form)
    {
        $properties = $action->getProperties();

        if (isset($fields[$properties['formField']])) {
            $fieldName = $fields[$properties['formField']]['alias'];
            if (isset($post[$fieldName])) {
                $model = $factory->getModel('lead.lead');
                $em    = $factory->getEntityManager();
                $leads = $em->getRepository('MauticLeadBundle:Lead')->getLeadsByFieldValue(
                    $properties['leadField'],
                    $fieldName
                );

                //check for existing IP address or add one if not exist
                $ip        = $server['REMOTE_ADDR'];
                $ipAddress = $em->getRepository('MauticCoreBundle:IpAddress')
                    ->findOneByIpAddress($ip);

                if ($ipAddress === null) {
                    $ipAddress = new IpAddress();
                    $ipAddress->setIpAddress($ip, $factory->getSystemParameters());
                }

                //create a new score change event
                $event = new ScoreChangeLog();
                $event->setType('form');
                $event->setEventName($form->getId() . ":" . $form->getName());
                $event->setActionName($action->getName());
                $event->setIpAddress($ipAddress);
                $event->setDateAdded(new \DateTime());

                if ($count = count($leads) === 1) {
                    //good to go so update the score
                    $lead = $leads[0];
                } else {
                    switch ($properties['matchMode']) {
                        case 'strict':
                            //no score change since more than one lead matched
                            $lead = false;
                            break;
                        case 'newest':
                            //the newest lead is listed first so use it
                            $lead = $leads[0];
                            break;
                        case 'oldest':
                            //the last lead is the oldest so use it
                            $lead = end($leads);
                            break;
                        case 'all':
                            $lead = false;

                            foreach ($leads as &$l) {
                                $delta = self::updateScore($l, $properties['operator'], $properties['score']);
                                $event->setDelta($delta);
                                $event->setLead($l);
                                $l->addScoreChangeLog($event);

                                $ipAddresses = $l->getIpAddresses();
                                //add the IP if the lead is not already associated with it
                                if (!$ipAddresses->contains($ipAddress)) {
                                    $l->addIpAddress($ipAddress);
                                }
                            }

                            $model->saveEntities($leads, false);
                            break;
                    }
                }

                if ($lead) {
                    $delta = self::updateScore($lead, $properties['operator'], $properties['score']);
                    $event->setDelta($delta);
                    $event->setLead($lead);
                    $lead->addScoreChangeLog($event);

                    $ipAddresses = $lead->getIpAddresses();
                    //add the IP if the lead is not already associated with it
                    if (!$ipAddresses->contains($ipAddress)) {
                        $lead->addIpAddress($ipAddress);
                    }

                    $model->saveEntity($lead, false);
                }
            }
        }
    }

    /**
     * @param $lead
     * @param $operator
     * @param $delta
     */
    private static function updateScore(&$lead, $operator, $delta)
    {
        $newScore = $originalScore = $lead->getScore();

        switch ($operator) {
            case 'plus':
                $newScore += $delta;
                break;
            case 'minus':
                $newScore -= $delta;
                break;
            case 'times':
                $newScore *= $delta;
                break;
            case 'divide':
                $newScore /= $delta;
                break;
        }

        $lead->setScore($newScore);

        return $newScore - $originalScore;
    }
}