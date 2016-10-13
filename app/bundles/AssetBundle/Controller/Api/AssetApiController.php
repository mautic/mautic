<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\AssetBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\AssetBundle\Entity\Asset;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class AssetApiController.
 */
class AssetApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('asset');
        $this->entityClass      = 'Mautic\AssetBundle\Entity\Asset';
        $this->entityNameOne    = 'asset';
        $this->entityNameMulti  = 'assets';
        $this->permissionBase   = 'asset:assets';
        $this->serializerGroups = ['assetDetails', 'categoryList', 'publishDetails'];
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before going through serializer.
     *
     * @param        $entity
     * @param string $action
     *
     * @return mixed
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $entity->setDownloadUrl(
            $this->model->generateUrl($entity, true)
        );
    }

    /**
     * Creates a new entity.
     *
     * @return Response
     */
    public function newEntityAction()
    {
        $entity = $this->model->getEntity();

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        $parameters = $this->request->request->all();
        $file       = $this->request->files->get('file');

        $entity->setTempId(uniqid('tmp_'));
        // $entity->setTempName();
        $entity->setMaxSize(Asset::convertSizeToBytes($this->factory->getParameter('max_size').'M')); // convert from MB to B
        $entity->setUploadDir($this->factory->getParameter('upload_dir'));
        $entity->preUpload();
        $entity->upload();

        return $this->processForm($entity, $parameters, 'POST');
    }
}
