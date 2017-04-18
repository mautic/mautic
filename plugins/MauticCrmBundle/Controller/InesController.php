<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Event\LeadTimelineEvent;


/**
 * Class InesController
 */
class InesController extends FormController
{
	// Page qui affiche la file d'attente des leads à synchroniser / supprimer avec INES
    public function logsAction()
    {
		// TESTS & DEBUG
		// $inesIntegration = $this->factory->getHelper('integration')->getIntegrationObject('Ines');
		// $leadModel = $this->factory->getModel('lead.lead');
        // $leadRepo = $leadModel->getRepository();
		// $inesIntegration->getApiHelper()->syncLeadToInes($leadRepo->getEntity(18));
		// die();

		$inesSyncLogModel = $this->factory->getModel('crm.ines_sync_log');

		$limit = 200;
		$items = $inesSyncLogModel->getAllEntities($limit);

		return $this->delegateView(array(
			'viewParameters' => array(
				'items' => $items
			),
			'contentTemplate' => 'MauticCrmBundle:Integration\Ines:logs.html.php'
		));
    }


    // TODO : commenter cette méthode, utilisée pour le debug
    public function debugAction()
    {
        $log_file = __DIR__.'/../ines.log';
        if (file_exists($log_file)) {
            $lines = explode(PHP_EOL, file_get_contents($log_file));
        }
        else {
            echo 'Log non trouvé';
            die();
        }
        $lines = array_reverse($lines);
        foreach($lines as $line) {
            $datas = json_decode($line, true);
            if (isset($datas['method'])) {
                echo '<h3>'.$datas['time'].' '.$datas['method'].'</h3>';
                echo '<pre>';
                var_dump($datas['parameters']);
                echo '</pre>';
                echo '<hr/>';
            }
            else {
                echo '<p>'.$datas['comment'].'</p>';
            }
        }
        die();
    }
}
