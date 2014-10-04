<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class AssetApiController
 *
 * @package Mautic\AssetBundle\Controller\Api
 */
class AssetApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('asset');
        $this->entityClass     = 'Mautic\AssetBundle\Entity\Asset';
        $this->entityNameOne   = 'asset';
        $this->entityNameMulti = 'assets';
        $this->permissionBase  = 'asset:assets';
        $this->serializerGroups = array("assetDetails", "categoryList", "publishDetails");
    }

    /**
     * Obtains a list of assets
     *
     * @ApiDoc(
     *   section = "Assets",
     *   description = "Obtains a list of assets",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   output={
     *      "class"="Mautic\AssetBundle\Entity\Asset",
     *      "groups"={"assetDetails", "categoryList", "publishDetails"}
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|title|alias|language|downloadCount|uniqueDownloadCount|revision)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->security->isGranted('asset:assets:viewother')) {
            $this->listFilters = array(
                array(
                    'column' => 'a.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()
                )
            );
        }
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific asset
     *
     * @ApiDoc(
     *   section = "Assets",
     *   description = "Obtains a specific asset",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the asset was not found"
     *   },
     *   output={
     *      "class"="Mautic\AssetBundle\Entity\Asset",
     *      "groups"={"assetDetails", "categoryList", "publishDetails"}
     *   }
     * )
     *
     * @param int $id Asset ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }
}