<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Entity\Form;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;

class CampaignEventHelper
{

    /**
     * Determine if this campaign applies
     *
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function validateFormSubmit(Form $eventDetails = null, $event)
    {
        if ($eventDetails == null) {
            return true;
        }

        $limitToForms = $event['properties']['forms'];

        //check against selected forms
        if (!empty($limitToForms) && !in_array($eventDetails->getId(), $limitToForms)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if this campaign applies
     *
     * @param $eventDetails
     * @param $event
     *
     * @return bool
     */
    public static function validateFormValue(MauticFactory $factory, $event, Lead $lead)
    {
        if (!$lead || !$lead->getId()) {
            return false;
        }

        $model     = $factory->getModel('form');
        $operators = $model->getFilterExpressionFunctions();
        $form      = $model->getRepository()->findOneById($event['properties']['form']);

        if (!$form || !$form->getId()) {
            return false;
        }

        return $factory->getModel('form.submission')->getRepository()->compareValue(
            $lead->getId(),
            $form->getId(),
            $form->getAlias(),
            $event['properties']['field'],
            $event['properties']['value'],
            $operators[$event['properties']['operator']]['expr']
        );
    }
}
