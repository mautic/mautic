<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\SocialMedia;
use Mautic\LeadBundle\SocialMedia\SocialIntegrationHelper;

class SocialController extends FormController
{

    public function indexAction()
    {
        $integrationHelper   = new SocialIntegrationHelper($this->get('mautic.factory'));
        $socialMediaServices = $integrationHelper->getSocialIntegrations();
        $em                  = $this->get('mautic.factory')->getEntityManager();
        $repo                = $em->getRepository('MauticLeadBundle:SocialMedia');
        $savedDetails        = $repo->findAll();
        $services = array();
        $found    = array();
        if (count($savedDetails)) {
            foreach ($savedDetails as $s) {
                $service    = $s->getService();
                $found[]    = $service;
                $services[$service] = $s;
            }
        }

        $available = array_keys($socialMediaServices);
        $missing   = array_diff($available, $found);

        foreach ($missing as $m) {
            $service = new SocialMedia();
            $service->setService($m);
            $services[$m] = $service;
        }

        //get a list of custom form fields
        $fields     = $this->get('mautic.factory')->getModel('lead.field')->getEntities();
        $leadFields = array();
        foreach ($fields as $f) {
            $leadFields[$f->getId()] = $f->getLabel();
        }

        //bind to the form
        $action   = $this->generateUrl('mautic_leadsocial_index');
        $services = $servicesCopy = array('services' => $services);

        $form = $this->createForm('socialmedia_config', $services, array(
            'action'       => $action,
            'integrations' => $socialMediaServices,
            'lead_fields'  => $leadFields
        ));

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    foreach ($services['services'] as $entity) {
                        $em->persist($entity);
                    }
                    $em->flush();
                }
            } else {
                //reset the form
                $form = $this->createForm('socialmedia_config', $servicesCopy, array(
                    'action'       => $action,
                    'integrations' => $socialMediaServices,
                    'lead_fields'  => $leadFields
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'     => $form->createView(),
                'services' => $socialMediaServices
            ),
            'contentTemplate' => "MauticLeadBundle:Social:index.html.php",
            'passthroughVars' => array(
                'activeLink'     => '#mautic_leadsocial_index',
                'mauticContent'  => 'leadSocial',
                'route'          => $action
            )
        ));
    }

    public function oAuth2CallbackAction($service)
    {
        //check to see if the service exists
        $class = "\\Mautic\\LeadBundle\\SocialMedia\\" . ucfirst($service);

        if (!class_exists($class)) {
            return $this->accessDenied('Not supported');
        }

        die('tes');
    }
}