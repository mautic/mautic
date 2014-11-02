<?php
/**
* @package     Mautic
* @copyright   2014 Mautic, NP. All rights reserved.
* @author      Mautic
* @link        http://mautic.com
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\MapperBundle\Entity\Application;
use Mautic\MapperBundle\Entity\ApplicationIntegration;
use Mautic\MapperBundle\Entity\ApplicationIntegrationRepository;
use Mautic\MapperBundle\Helper\ApiHelper;
use Mautic\MapperBundle\Helper\ApplicationIntegrationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapperController extends FormController
{
    public function indexAction()
    {
        if (!$this->factory->getSecurity()->isGranted('mapper:config:full')) {
            return $this->accessDenied();
        }

        $applications = ApplicationIntegrationHelper::getApplications($this->factory, null, null, true);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'applications' => $applications
            ),
            'contentTemplate' => "MauticMapperBundle:Dashboard:index.html.php",
            'passthroughVars' => array(
                'activeLink'     => '#mautic_mapper_index',
                'mauticContent'  => 'leadSocial',
                'route'          => ''
            )
        ));
    }

    public function saveAction($application)
    {
        $object = ApplicationIntegrationHelper::getApplication($this->factory, $application);

        $postActionVars = array(
            'returnUrl'       => $object->getAppLink(),
        );

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $entity = $object->getEntity();
            $entity->setApiKeys($object->getSettings($_POST));
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
        }

        return $this->postActionRedirect(
            array_merge($postActionVars)
        );
    }

    /**
     * Display Application if they're not setup already
     * If yes they try to authenticate
     * else they show object from particular application
     *
     * @param $application
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function integrationAction($application)
    {
        $appIntegration = ApplicationIntegrationHelper::getApplication($this->factory, $application);

        $viewData = array(
            'viewParameters' => array(
                'appIntegration' => $appIntegration
            ),
            'contentTemplate' => null
        );

        $integrationAuth = ApiHelper::getApiAuth($application, $appIntegration);

        if ($integrationAuth->validateAccessToken()) {
            ApiHelper::checkApiAuthentication($this->factory, $application, $appIntegration);
            $viewData['contentTemplate'] = 'MauticMapperBundle:Mapper:index.html.php';
            $viewData['viewParameters']['router'] = $this->factory->getRouter();
        } else {
            $viewData['viewParameters']['formName'] = 'form_'.$application;
            $viewData['contentTemplate'] = sprintf('MauticMapperBundle:Application:%s.html.php',$application);
        }

        return $this->delegateView($viewData);
    }

    public function integrationObjectAction($application, $object)
    {
        $appIntegration = ApplicationIntegrationHelper::getApplication($this->factory, $application);
        $objectEntity = $appIntegration->getMappedObject($object);

        ApiHelper::checkApiAuthentication($this->factory, $application, $appIntegration);

        $sourceFields = $appIntegration->getMauticObject($object);
        $targetFields = $appIntegration->getApiObject($object);
        $targetOptions = $appIntegration->getObjectOptions($object, $targetFields);

        $viewData = array(
            'viewParameters' => array(
                'appIntegration' => $appIntegration,
                'appObject' => $appIntegration->getMappedObject($object),
                'sourceFields' => $sourceFields,
                'targetFields' => $targetFields,
                'targetOptions' => $targetOptions
            ),
            'contentTemplate' => sprintf('MauticMapperBundle:Mapper:%s.fields.html.php', $application)
        );

        return $this->delegateView($viewData);
    }



    /**
     * Get Response from Authentication and store on database
     *
     * @param $application
     */
    public function oAuth2CallbackAction($application)
    {
        $appIntegration = ApplicationIntegrationHelper::getApplication($this->factory, $application);

        $postActionVars = array(
            'returnUrl'       => $appIntegration->getAppLink(),
        );

        ApiHelper::checkApiAuthentication($this->factory, $application, $appIntegration);

        die('return');

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );

    }
}
