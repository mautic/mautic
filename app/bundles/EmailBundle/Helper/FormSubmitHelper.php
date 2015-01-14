<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Action;

class FormSubmitHelper
{
	/**
     * @param       $action
     *
     * @return array
     */
    public static function sendEmail(Action $action, MauticFactory $factory, $feedback)
    {
        $properties = $action->getProperties();
        $emailId    = (int) $properties['email'];
        $form       = $action->getForm();

        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel  = $factory->getModel('email');
        $email  = $emailModel->getEntity($emailId);

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {

        	// Deal with Admin email
	        if (isset($properties['user_id']) && $properties['user_id']) {
		        $emailModel->sendEmailToUser($email, $properties['user_id']);
		    }

		    // Deal with Lead email
		    if (!empty($feedback['lead.create']['lead'])) {
		    	//the lead was just created via the lead.create action
		    	$currentLead = $feedback['lead.create']['lead'];
		    } else {
		        $leadModel = $factory->getModel('lead');
		        $currentLead = $leadModel->getCurrentLead();
		    }

		    if (isset($currentLead)) {
		    	$leadFields = $currentLead->getFields();
		    	if (isset($leadFields['core']['email']['value']) && $leadFields['core']['email']['value']) {
		    		$leadCredentials = array(
		    			'email' 	=> $leadFields['core']['email']['value'],
		    			'id' 		=> $currentLead->getId(),
		    			'firstname' => $leadFields['core']['firstname']['value'],
		    			'lastname' 	=> $leadFields['core']['lastname']['value']
		    		);
		    		$emailModel->sendEmail($email, array($leadCredentials['id'] => $leadCredentials), array('form', $form->getId()));
		    	}
		    }
        }
    }
}