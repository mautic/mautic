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

class SocialController extends FormController
{

    public function indexAction()
    {
        $factory        = $this->get('mautic.factory');

        if (!$factory->getSecurity()->isGranted('social:config:full')) {
            return $this->accessDenied();
        }

        $networkObjects = NetworkIntegrationHelper::getNetworkObjects($factory);
        $em             = $factory->getEntityManager();
        $services       = array();
        $currentKeys    = array(); //prevent overriding of secrets
        foreach ($networkObjects as $name => $service) {
            $services[$name]    = $service->getSettings();
            $currentKeys[$name] = $services[$name]->getApiKeys();
        }

        //get a list of custom form fields
        $fields     = $factory->getModel('lead.field')->getEntities();
        $leadFields = array();
        foreach ($fields as $f) {
            $leadFields[$f->getId()] = $f->getLabel();
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
        //check to see if the service exists
        $class = "\\Mautic\\SocialBundle\\Network\\" . ucfirst($network) . "Network";

        if (!class_exists($class)) {
            return $this->accessDenied('Not supported');
        }

        //make the callback the service to get the access code
        $networkObject = NetworkIntegrationHelper::getNetworkObjects($this->get('mautic.factory'), $network);
        list($entity, $error) = $networkObject->oAuthCallback();

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

        return $this->redirect($this->generateUrl('mautic_social_index'));
    }
}