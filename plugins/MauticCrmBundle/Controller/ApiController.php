<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\ApiBundle\Controller\CommonApiController;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiController
 * Ajoute des EndPoints à l'API de Mautic
 */
class ApiController extends CommonApiController
{
	/**
	 * Retourne la liste des champs INES mappés avec les champs ATMT
	 * Pour tester, utiliser l'API tester de Mautic : https://github.com/mautic/api-library/tree/master/apitester
	 * Chemin : /api/ines/getMapping
	 *
	 * @return 	JsonResponse(array)
	 */
	public function inesGetMappingAction()
	{
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');

		$mappedFields = $inesIntegration->getMapping();
		$dontSyncFieldKey = $inesIntegration->getDontSyncAtmtKey();

		return new JsonResponse(array(
			'mapping' => $mappedFields,
			'dontSyncFieldKey' => $dontSyncFieldKey
		));
	}
}
