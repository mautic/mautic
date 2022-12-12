<?php

namespace Mautic\AssetBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Asset>
 */
class AssetApiController extends CommonApiController
{
    /**
     * @var AssetModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $assetModel = $this->getModel('asset');
        \assert($assetModel instanceof AssetModel);

        $this->model            = $assetModel;
        $this->entityClass      = Asset::class;
        $this->entityNameOne    = 'asset';
        $this->entityNameMulti  = 'assets';
        $this->serializerGroups = ['assetDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before going through serializer.
     */
    protected function preSerializeEntity(object $entity, string $action = 'view'): void
    {
        $entity->setDownloadUrl(
            $this->model->generateUrl($entity, true)
        );
    }

    /**
     * Convert posted parameters into what the form needs in order to successfully bind.
     *
     * @param $parameters
     * @param $entity
     * @param $action
     *
     * @return mixed
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        $assetDir = $this->get('mautic.helper.core_parameters')->get('upload_dir');
        $entity->setUploadDir($assetDir);

        if (isset($parameters['file'])) {
            if ('local' === $parameters['storageLocation']) {
                $entity->setPath($parameters['file']);
                $entity->setFileInfoFromFile();

                if (null === $entity->loadFile()) {
                    return $this->returnError('File '.$parameters['file'].' was not found in the asset directory.', Response::HTTP_BAD_REQUEST);
                }
            } elseif ('remote' === $parameters['storageLocation']) {
                $parameters['remotePath'] = $parameters['file'];
                $entity->setTitle($parameters['title']);
                $entity->setStorageLocation('remote');
                $entity->setRemotePath($parameters['remotePath']);
                $entity->preUpload();
                $entity->upload();
            }

            unset($parameters['file']);
        } elseif ('new' === $action) {
            return $this->returnError('File of the asset is required.', Response::HTTP_BAD_REQUEST);
        }

        return $parameters;
    }
}
