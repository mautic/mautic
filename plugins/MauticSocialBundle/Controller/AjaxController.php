<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\EmailBundle\Controller
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getNetworkFormAction(Request $request)
    {
        // get the form type
        $type       = InputHelper::clean($request->request->get('networkType'));

        // default to empty
        $dataArray = array (
            'html' => '',
            'success' => 0
        );

        if (!empty($type)) {
            //get the HTML for the form

            // get the network type form
            $form = $this->get('form.factory')->create($type, array(), array('label' => false, 'csrf_protection' => false));

            $html = $this->renderView('MauticSocialBundle:FormTheme:' . $type .  '_widget.html.php',
                array( 'form' => $form->createView() )
            );

            $html = str_replace(array(
                    $type . '[', // this is going to generate twitter_hashtag[ or twitter_mention[
                    $type . '_', // this is going to generate twitter_hashtag_ or twitter_mention_
                    $type
                ), array(
                    'monitoring[properties][',
                    'monitoring_properties_',
                    'monitoring'
                ), $html);


            $dataArray['html']    = $html;
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}