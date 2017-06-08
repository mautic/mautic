<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Exception as MauticException;
use Joomla\Http\Http;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MessengerController extends FormController
{


    /**
     *
     */
    public function callbackAction()
    {
        $access_token = "EAAH5qCyWCdcBAP0ddTZBNbVRdmqd43TZCnBJGFEwRZAmO76hlrXfWmVzBXO5xEsochEnlrQ88Tkrwm2B63KzXctLxXQ8RU6KKM9sWEFsGZAaBzmmMUoqVjfir1n5ufXgW8btvZAL41bNJ5S0IceHKUCioOLTqCLZCZCOOMlNz5fRAZDZD";
        $verify_token = "bot_app";
        $hub_verify_token = null;
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $hub_verify_token = $_REQUEST['hub_verify_token'];
            if ($hub_verify_token === $verify_token) {
                return new Response($challenge);
            }
        }

    }
}
