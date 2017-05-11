<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;

/**
 * Class InesController.
 */
class InesController extends FormController
{
    /**
     * Page : INES sync log for full-sync mode.
     *
     * @return string
     */
    public function logsAction()
    {
        $inesSyncLogModel = $this->getModel('crm.ines_sync_log');

        $limit = 200;
        $items = $inesSyncLogModel->getAllEntities($limit);

        return $this->delegateView([
            'viewParameters' => [
                'items' => $items,
            ],
            'contentTemplate' => 'MauticCrmBundle:Integration\Ines:logs.html.php',
        ]);
    }
}
