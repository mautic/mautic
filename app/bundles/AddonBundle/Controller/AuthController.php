<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthController
 */
class AuthController extends FormController
{
    /**
     * @param string $integration
     *
     * @return JsonResponse
     */
    public function oAuth2CallbackAction ($integration)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $integrationObjects array keys to lowercase
        $objects = array();

        foreach ($integrationObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        //check to see if the service exists
        if (!array_key_exists(strtolower($integration), $objects)) {
            $this->addFlash('mautic.integration.notfound', array('%name%' => $integration), 'error');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticAddonBundle:Auth:postauth.html.php');
            }
        }

        $session    = $this->factory->getSession();
        $state      = $session->get($integration . '_csrf_token', false);

        $givenState = ($isAjax) ? $this->request->request->get('state') : $this->request->get('state');
        if ($state && $state !== $givenState) {
            $this->addFlash('mautic.integration.auth.invalid.state', array(), 'error');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticAddonBundle:Auth:postauth.html.php');
            }
        }

        list($entity, $error) = $integrationObjects[$integration]->oAuthCallback();

        //check for error
        if ($error) {
            $type    = 'error';
            $message = 'mautic.integration.error.oauthfail';
            $params  = array('%error%' => $error);
        } else {
            $type    = 'notice';
            $message = 'mautic.integration.notice.oauthsuccess';
            $params  = array();
        }

        $this->addFlash($message, $params, $type);

        return new RedirectResponse($this->generateUrl('mautic_integration_oauth_postauth'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function oAuthStatusAction ()
    {
        return $this->render('MauticAddonBundle:Auth:postauth.html.php');
    }
}
