<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGrapeJsBundle\Uploader;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\PathsHelper;
use MauticPlugin\MauticBadgeGeneratorBundle\Entity\Badge;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileManager
{
    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var array
     */
    private $uploadFilesName = ['files'];

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * FileManager constructor.
     *
     * @param FileUploader         $fileUploader
     * @param CoreParametersHelper $coreParametersHelper
     * @param PathsHelper          $pathsHelper
     */
    public function __construct(
        FileUploader $fileUploader,
        CoreParametersHelper $coreParametersHelper,
        PathsHelper $pathsHelper
    ) {
        $this->fileUploader         = $fileUploader;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->pathsHelper          = $pathsHelper;
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function uploadFiles($request)
    {
        if (isset($request->files->all()['files'])) {
            $files = $request->files->all()['files'];
            $uploadDir = $this->getUploadDir();
            $uploadedFiles = [];
            foreach ($files as $file) {
                try {
                    $uploadedFiles[] =  $this->getFullUrl($this->fileUploader->upload($uploadDir, $file));
                } catch (FileUploadException $e) {
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param Badge $entity
     * @param string       $fileName
     *
     * @return string
     */
    public function getCompleteFilePath($fileName)
    {
        $uploadDir = $this->getUploadDir();

        return $uploadDir.$fileName;
    }

    /**
     * @param Badge $entity
     *
     * @return string
     */
    private function getUploadDir()
    {
        return $this->getGrapesJsImagePath(true);
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    public function getFullUrl($fileName)
    {
        return $this->coreParametersHelper->getParameter(
            'site_url'
        ).'/'.$this->getGrapesJsImagePath(false, '/').$fileName;
    }

    /**
     * @param bool   $fullPath
     * @param string $slash
     *
     * @return string
     */
    private function getGrapesJsImagePath($fullPath = false , $slash = DIRECTORY_SEPARATOR)
    {
        return $this->pathsHelper->getSystemPath(
            'images',
            $fullPath
        ).$slash.$this->coreParametersHelper->getParameter(
            'grapes_js_image_directory'
        ).$slash;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        $files = [];
        $uploadDir = $this->getUploadDir();
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($uploadDir)) {
            try {
                $fileSystem->mkdir($uploadDir);
            } catch (IOException $exception) {
                return $files;
            }
        }

        $finder = new Finder();
        $finder->files()->in($uploadDir);
        foreach ($finder as $file) {
            $files[] = $this->getFullUrl($file->getFilename());
        }
        return $files;
    }

    /**
     * @return array
     */
    public function getUploadFilesName()
    {
        return $this->uploadFilesName;
    }
}