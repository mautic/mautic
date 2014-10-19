<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class SocialController extends FormController
{

    public function indexAction()
    {
        if (!$this->factory->getSecurity()->isGranted('social:config:full')) {
            return $this->accessDenied();
        }

        $networkObjects = NetworkIntegrationHelper::getNetworkObjects($this->factory, null, null, true);
        $em             = $this->factory->getEntityManager();
        $services       = array();
        $currentKeys    = array(); //prevent overriding of secrets
        foreach ($networkObjects as $name => $service) {
            $services[$name]    = $service->getSettings();
            $currentKeys[$name] = $services[$name]->getApiKeys();
        }

        //get a list of custom form fields
        $fields     = $this->factory->getModel('lead.field')->getEntities(
            array('filter' => array('isPublished' => true))
        );
        $leadFields = array();
        foreach ($fields as $f) {
            $leadFields['mautic.lead.field.group.'. $f->getGroup()][$f->getId()] = $f->getLabel();
        }
        //sort the groups
        uksort($leadFields, "strnatcmp");

        //sort each group by translation
        foreach ($leadFields as $group => &$fieldGroup) {
            uasort($fieldGroup, "strnatcmp");
        }

        //bind to the form
        $action   = $this->generateUrl('mautic_social_index');
        $services = $servicesCopy = array('services' => $services);

        $form = $this->createForm('socialmedia_config', $services, array(
            'action'       => $action,
            'integrations' => $networkObjects,
            'lead_fields'  => $leadFields
        ));

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    foreach ($services['services'] as $network => $entity) {
                        //check to make sure secret keys were not wiped out
                        if (!empty($currentKeys[$network]['clientId'])) {
                            $newKeys = $entity->getApiKeys();
                            if (!empty($currentKeys[$network]['clientSecret']) && empty($newKeys['clientSecret'])) {
                                $newKeys['clientSecret'] = $currentKeys[$network]['clientSecret'];
                                $entity->setApiKeys($newKeys);
                            }
                        }

                        $features = $entity->getSupportedFeatures();
                        if (in_array('public_profile', $features)) {
                            //make sure now non-existent aren't saved
                            $featureSettings               = $entity->getFeatureSettings();
                            if (isset($featureSettings['leadFields'])) {
                                $fields                        = NetworkIntegrationHelper::getAvailableFields($this->factory, $network);
                                $featureSettings['leadFields'] = array_intersect_key($featureSettings['leadFields'], $fields);
                                $entity->setFeatureSettings($featureSettings);
                            }
                        }

                        $em->persist($entity);
                    }
                    $em->flush();

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.social.notice.saved', array(), 'flashes')
                    );

                    //redirect if not ajaxified
                    if (!$this->request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('mautic_social_index'));
                    }
                }
            } else {
                //reset the form
                $form = $this->createForm('socialmedia_config', $servicesCopy, array(
                    'action'       => $action,
                    'integrations' => $networkObjects,
                    'lead_fields'  => $leadFields
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'     => $form->createView(),
                'services' => $networkObjects
            ),
            'contentTemplate' => "MauticSocialBundle:Social:index.html.php",
            'passthroughVars' => array(
                'activeLink'     => '#mautic_social_index',
                'mauticContent'  => 'leadSocial',
                'route'          => $action
            )
        ));
    }

    public function oAuth2CallbackAction($network)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        //check to see if the service exists
        $class = "\\Mautic\\SocialBundle\\Network\\" . ucfirst($network) . "Network";

        if (!class_exists($class)) {
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
        } else {
            //access token obtained so now get it and save it

            $session->remove($network . '_csrf_token');

            //make the callback the service to get the access code
            $networkObjects = NetworkIntegrationHelper::getNetworkObjects($this->factory, $network);

            $clientId     = $this->request->request->get('clientId');
            $clientSecret = $this->request->request->get('clientSecret');

            list($entity, $error) = $networkObjects[$network]->oAuthCallback($clientId, $clientSecret);

            //check for error
            if ($error) {
                $this->request->getSession()->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('mautic.social.error.oauthfail', array('%error%' => $error), 'flashes')
                );
            } else {
                $this->request->getSession()->getFlashBag()->add(
                    'notice',
                    $this->get('translator')->trans('mautic.social.notice.oauthsuccess', array(), 'flashes')
                );
            }

            return new JsonResponse(array('url' => $this->generateUrl('mautic_social_postauth')));
        }
    }

    public function oAuthStatusAction()
    {
        return $this->render('MauticSocialBundle:Social:postauth.html.php');
    }
}