<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{
    /**
	 * INES : teste la connexion au web-service, utilisé par le bouton de test de l'onglet de config du plugin
     *
	 * @param 	\Symfony\Component\HttpFoundation\Request		$request 	Laisser vide : aucun paramètre requis
     * @return 	\Symfony\Component\HttpFoundation\JsonResponse	string		Message de succès ou d'échec
     */
    protected function inesCheckConnexionAction(Request $request)
    {
		$inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');

		$isConnexionOk = $inesIntegration->checkAuth();

		$message = $this->factory->getTranslator()->trans(
			$isConnexionOk ? 'mautic.ines.form.check.success' : 'mautic.ines.form.check.fail'
		);

		return $this->sendJsonResponse(array(
			'message' => $message
		));
    }
}
