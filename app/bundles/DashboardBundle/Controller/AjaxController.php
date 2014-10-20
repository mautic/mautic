<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

/**
 * Class AjaxController
 *
 * @package Mautic\DashboardBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function mapDataAction (Request $request)
    {
        $dataArray  = array('success' => 0, 'stats' => array());

        $countries = array_flip(Intl::getRegionBundle()->getCountryNames());

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepository */
        $leadRepository = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');
        $leadCountries = $leadRepository->getLeadsCountPerCountries();

        // Convert country names to 2-char code
        foreach ($leadCountries as $leadCountry) {
            if (isset($countries[$leadCountry['country']])) {
                $dataArray['stats'][strtolower($countries[$leadCountry['country']])] = $leadCountry['quantity'];
            }
        }

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
