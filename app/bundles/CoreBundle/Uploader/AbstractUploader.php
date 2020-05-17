<?php
/*
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Uploader;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Uploader\File\FileProperty;
use Mautic\CoreBundle\Uploader\Locator\DirectoryLocator;
use Mautic\CoreBundle\Uploader\Locator\FileLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractUploader
{
    const SYSTEM_PATH = 'assets';

    private $coreParametersHelper;

    private $pathsHelper;

    private $propertyAccessor;

    private $fileUploader;

    private $request;

    /** @var CommonEntity */
    private $entity;

    /** @var string */
    private $field;

    /**
     * UploadDecoratorPath constructor.
     */
    public function __construct(FileUploader $fileUploader, RequestStack $requestStack, CoreParametersHelper $coreParametersHelper, PathsHelper $pathsHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->pathsHelper          = $pathsHelper;
        $this->request              = $requestStack->getCurrentRequest();
        $this->fileUploader         = $fileUploader;
        $this->propertyAccessor     = new PropertyAccessor();
        $this->directoryLocator     = new DirectoryLocator($this);
    }

    /**
     * @param CommonEntity|null $entity
     *
     * @return bool
     */
    public function uploadFiles($entity = null)
    {
        $this->entity = $entity;
        $files        = [];

        if (isset($this->request->files->all()[$this->getForm()])) {
            $files = $this->request->files->all()[$this->getForm()];
        }

        $entityChange = false;

        foreach ($this->getFields() as $field) {
            $this->field = $field;
            $fileLocator = new FileLocator($this);
            // nothing for upload
            if (empty($files[$field])) {
                // Delete
                if (!empty($this->request->request->all()[$this->getForm()][$field.'_remove'])) {
                    $this->fileUploader->delete($fileLocator->getFilePath($this->getFileNameFromEntity($field)));
                    // set empty to entity
                    if ($entity) {
                        $this->propertyAccessor->setValue($entity, $field, '');
                        $entityChange = true;
                    }
                }
                continue;
            }

            $file = $files[$field];

            try {
                $uploadedFile = $this->fileUploader->upload($this->directoryLocator->getUploadPathDirectory(), $file);
                if ($entity) {
                    $this->propertyAccessor->setValue($entity, $field, $uploadedFile);
                    $entityChange = true;
                }
            } catch (FileUploadException $e) {
            }
        }

        return $entityChange;
    }

    /**
     * @param $entity
     */
    public function removeFiles($entity)
    {
        $this->entity = $entity;
        $this->fileUploader->delete($this->directoryLocator->getUploadPathDirectory());
    }

    /**
     * @param $field
     *
     * @return bool|Response
     */
    public function downloadFile($entity, $field)
    {
        if ($entity) {
            $this->entity = $entity;
            $fileLocator  = new FileLocator($this);
            $fileName     = $this->getFileNameFromEntity($field);
            $filePath     = $fileLocator->getFilePath($fileName);
            $file         = new FileProperty($filePath);

            $response = new Response();
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Content-Type', $file->getFileMimeType());
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$fileName);
            $response->setContent(file_get_contents($filePath));

            return $response;
        }

        throw new FileNotFoundException();
    }

    /**
     * System path always assets.
     *
     * @return string
     */
    public function getSystemPathDirectory()
    {
        return 'assets';
    }

    /**
     * Directory path.
     *
     * @return array
     */
    public function getUploadDirectory()
    {
        return ['files'];
    }

    /**
     * @return CoreParametersHelper
     */
    public function getCoreParametersHelper()
    {
        return $this->coreParametersHelper;
    }

    /**
     * @return PathsHelper
     */
    public function getPathsHelper()
    {
        return $this->pathsHelper;
    }

    /**
     * @return CommonEntity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getFileNameFromEntity($field)
    {
        return $this->propertyAccessor->getValue($this->entity, $field);
    }
}
