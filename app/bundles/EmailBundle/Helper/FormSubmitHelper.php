<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
        $emailId    = $properties['email'];
        $form       = $action->getForm();

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model  = $factory->getModel('email');
        $email  = $model->getEntity($emailId);

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {

        	// Deal with Admin email
	        if (isset($properties['user_id']) && $properties['user_id']) {
		        $model->sendEmailToUser($email, $properties['user_id']);
		    }

		    // Deal with Lead email
		    if (!empty($feedback['lead.create']['lead'])) {
		    	//the lead was just created via the lead.create action
		    	$currentLead = $feedback['lead.create']['lead'];
		    } else {
		        $model = $factory->getModel('lead');
		        $currentLead = $model->getCurrentLead();
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
		    		$model->sendEmail($email, array($leadCredentials['id'] => $leadCredentials), array('form', $form->getId()));
		    	}
		    }
        }
    }
}