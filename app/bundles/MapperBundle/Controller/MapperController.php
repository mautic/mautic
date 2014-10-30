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
use Mautic\MapperBundle\Helper\NetworkIntegrationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapperController extends FormController
{
    public function indexAction()
    {
        if (!$this->factory->getSecurity()->isGranted('mapper:config:full')) {
            return $this->accessDenied();
        }


    }

    public function oAuth2CallbackAction($network)
    {

    }

    public function oAuthStatusAction()
    {
        return $this->render('MauticSocialBundle:Social:postauth.html.php');
    }
}
