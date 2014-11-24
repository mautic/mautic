<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SocialController
 */
class SocialController extends FormController
{
    /**
     * @param string $network
     *
     * @return JsonResponse
     */
    public function oAuth2CallbackAction($network)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        /** @var \Mautic\IntegrationBundle\Helper\NetworkIntegrationHelper $networkHelper */
        $networkHelper  = $this->container->get('mautic.network.integration');
        $networkObjects = $networkHelper->getNetworkObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $networkObjects array keys to lowercase
        $objects = array();

        foreach ($networkObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        //check to see if the service exists
        if (!array_key_exists(strtolower($network), $objects)) {
            $this->request->getSession()->getFlashBag()->add('error', $network . ' not found!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_social_postauth')));
            } else {
                return $this->render('MauticSocialBundle:Social:postauth.html.php');
            }
        }

        $session    = $this->factory->getSession();
        $state      = $session->get($network . '_csrf_token', false);
        $givenState = ($isAjax) ? $this->request->request->get('state') : $this->request->get('state');
        if ($state && $state !== $givenState) {
            $this->request->getSession()->getFlashBag()->add('error', 'Invalid CSRF token!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_social_postauth')));
            } else {
                return $this->render('MauticSocialBundle:Social:postauth.html.php');
            }
        }

        if (!$isAjax) {
            //redirected from SM site with code so obtain access_token via ajax

            return $this->render('MauticSocialBundle:Social:auth.html.php', array(
                'network'     => $network,
                'csrfToken'   => $state,
                'code'        => $this->request->get('code'),
                'callbackUrl' => $this->generateUrl('mautic_social_callback', array('network' => $network), true)
            ));
        }

        //access token obtained so now get it and save it
        $session->remove($network . '_csrf_token');

        //make the callback to the service to get the access code
        $clientId     = $this->request->request->get('clientId');
        $clientSecret = $this->request->request->get('clientSecret');

        list($entity, $error) = $networkObjects[$network]->oAuthCallback($clientId, $clientSecret);

        //check for error
        if ($error) {
            $type = 'error';
            $message = 'mautic.social.error.oauthfail';
            $params = array('%error%' => $error);
        } else {
            $type = 'notice';
            $message = 'mautic.social.notice.oauthsuccess';
            $params = array();
        }

        $this->request->getSession()->getFlashBag()->add(
            $type,
            $this->get('translator')->trans($message, $params, 'flashes')
        );

        return new JsonResponse(array('url' => $this->generateUrl('mautic_social_postauth')));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function oAuthStatusAction()
    {
        return $this->render('MauticSocialBundle:Social:postauth.html.php');
    }
}
