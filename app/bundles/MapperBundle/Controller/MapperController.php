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

    public function integrationAction($network)
    {

    }

    public function integrationObjectAction($network, $object)
    {

    }

    public function oAuth2CallbackAction($network)
    {

    }
}
