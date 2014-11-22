<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\SubscribedEvents;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Helper\EmailTokenHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EmailTokenController
 */
class EmailTokenController extends FormController
{

    /**
     * @param int $page
     *
     * @return JsonResponse
     */
    public function indexAction($page = 1)
    {
        $tokenHelper = new EmailTokenHelper($this->factory);

        $dataArray = array(
            'newContent'     => $tokenHelper->getTokenContent($page),
            'mauticContent'  => 'emailEditor'
        );

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }
}