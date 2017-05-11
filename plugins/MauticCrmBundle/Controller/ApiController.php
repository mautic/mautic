<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiController.
 */
class ApiController extends CommonApiController
{
    /**
     * API end-point : /api/ines/getMapping
     * Returns the list of INES fields mapped with the ATMT fields
     * To test, use the Mautic test API : https://github.com/mautic/api-library/tree/master/apitester.
     *
     * @return JsonResponse(array)
     */
    public function inesGetMappingAction()
    {
        $inesIntegration = $this->container->get('mautic.helper.integration')->getIntegrationObject('Ines');

        $mappedFields     = $inesIntegration->getMapping();
        $dontSyncFieldKey = $inesIntegration->getDontSyncAtmtKey();

        return new JsonResponse([
            'mapping'          => $mappedFields,
            'dontSyncFieldKey' => $dontSyncFieldKey,
        ]);
    }
}
