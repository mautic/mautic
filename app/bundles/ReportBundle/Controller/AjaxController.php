<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\ReportBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Update the column lists
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateColumnsAction(Request $request)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model  = $this->factory->getModel('report');
        $tables = $model->getTableData();

        $dataArray = array(
            'columns' => $tables[$request->get('table')]['columns']
        );

        return $this->sendJsonResponse($dataArray);
    }
}
