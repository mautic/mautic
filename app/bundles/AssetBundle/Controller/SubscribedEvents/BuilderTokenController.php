<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller\SubscribedEvents;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\AssetBundle\Helper\BuilderTokenHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BuilderTokenController
 */
class BuilderTokenController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse
     */
    public function indexAction($page = 1)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory);

        $dataArray = array(
            'newContent'     => $tokenHelper->getTokenContent($page),
            'mauticContent'  => 'builder'
        );

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }
}