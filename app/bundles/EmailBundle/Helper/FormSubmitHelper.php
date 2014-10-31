<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Entity\Email;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Action;
use Symfony\Component\HttpFoundation\Response;

class FormSubmitHelper
{
	/**
     * @param       $action
     *
     * @return array
     */
    public static function onFormSubmit(Action $action, MauticFactory $factory)
    {
        $properties = $action->getProperties();
        $emailId  = $properties['email'];

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model  = $factory->getModel('email');
        $email  = $model->getEntity($emailId);

        //make sure the email still exists and is published
        if ($email != null && $email->isPublished()) {
            //register a callback after the other actions have been fired
            return array(
                'callback' => '\Mautic\EmailBundle\Helper\FormSubmitHelper::sendEmail',
                'email'    => $email,
                'message'  => $properties['message']
            );
        }
    }

    public static function sendEmail(Email $email, MauticFactory $factory, $message)
    {
    	/** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model  = $factory->getModel('email');
        $url = $model->generateUrl($email, true);

        $msg = $message . $factory->getTranslator()->trans('mautic.asset.asset.submitaction.downloadfile.msg', array(
            '%url%' => $url
        ));

        //@todo - give option to choose a template
        $content = $factory->getTemplating()->renderResponse('MauticEmailBundle::message.html.php', array(
            'message'  => $msg,
            'type'     => 'notice',
            'template' => $factory->getParameter('theme')
        ))->getContent();

        return new Response($content);
    }
}