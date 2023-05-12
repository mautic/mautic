<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Helper;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileManager
{
    const GRAPESJS_IMAGES_DIRECTORY = '';

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * FileManager constructor.
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
            $files         = $request->files->all()['files'];
            $uploadDir     = $this->getUploadDir();
            $uploadedFiles = [];

            $activeDirectory = $request->request->get('activeDirectory') ?: '';

            if ($activeDirectory && 'root' !== $activeDirectory) {
                $uploadDir .= $activeDirectory;
            }

            foreach ($files as $file) {
                try {
                    $uploadedFile    = $this->fileUploader->upload($uploadDir, $file);

                    if ($activeDirectory && 'root' !== $activeDirectory) {
                        $uploadedFile = $activeDirectory.'/'.$uploadedFile;
                    }

                    $uploadedFiles[] =  $this->getFullUrl($uploadedFile);
                } catch (FileUploadException $e) {
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param string $fileName
     */
    public function deleteFile($fileName)
    {
        $this->fileUploader->delete($this->getCompleteFilePath($fileName));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getCompleteFilePath($fileName)
    {
        $uploadDir = $this->getUploadDir();

        return $uploadDir.$fileName;
    }

    /**
     * @return string
     */
    private function getUploadDir()
    {
        return $this->getGrapesJsImagesPath(true);
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    public function getFullUrl($fileName, $separator = '/')
    {
        // if a static_url (CDN) is configured use that, otherwiese use the site url
        $url = $this->coreParametersHelper->getParameter('static_url') ?? $this->coreParametersHelper->getParameter('site_url');

        return $this->removeTrailingSlash($url)
            .$separator
            .$this->getGrapesJsImagesPath(false, $separator)
            .$fileName;
    }

    /**
     * @param bool   $fullPath
     * @param string $separator
     *
     * @return string
     */
    private function getGrapesJsImagesPath($fullPath = false, $separator = '/')
    {
        // Lets do some stuff to figure out our deployment strategy.
        $fileSystem         = new Filesystem();
        $deployedImagesPath = '/var/www/srv/shared/media/images2/';

        if ($fileSystem->exists($deployedImagesPath)) {
            if (!$fullPath) {
                $deployedImagesPath = str_replace('/var/www/srv/shared/', '', $deployedImagesPath);
            }

            return $deployedImagesPath;
        }

        return $this->pathsHelper->getSystemPath('images', $fullPath)
            .$separator
            .self::GRAPESJS_IMAGES_DIRECTORY;
    }

    /**
     * @return array
     */
    public function getImages($subPath = '')
    {
        $files      = [];
        $uploadDir  = $this->getUploadDir();

        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($uploadDir)) {
            try {
                $fileSystem->mkdir($uploadDir);
            } catch (IOException $exception) {
                return $files;
            }
        }

        $uploadDir         = $this->getUploadDir();
        $originalUploadDir = $uploadDir;

        $subPath = preg_replace('/[^A-Za-z0-9 \/_-]/', '', $subPath ?: '');

        if ($subPath && 'root' !== $subPath) {
            // filter naughty paths

            $uploadDir .= $subPath;

            if (!$fileSystem->exists($uploadDir)) {
                throw new NotFoundHttpException();
            }

            $files[] = [
                'type'         => 'directory',
                'path'         => 'root',
                'absolutePath' => 'root',
                'icon'         => 'fa fa-home',
            ];

            // Do we have a navigatable dir?
            $parentLevel = substr($uploadDir, 0, strrpos($uploadDir, '/') + 1);

            if ($originalUploadDir !== $parentLevel) {
                $files[] = [
                    'type'         => 'directory',
                    'path'         => '..',
                    'absolutePath' => str_replace($originalUploadDir, '', rtrim($parentLevel, '/')),
                    'icon'         => 'fa fa-arrow-up',
                ];
            }
        }

        $finder = new Finder();
        $finder->depth('== 0')
            ->in($uploadDir);

        foreach ($finder as $file) {
            // exclude certain folders from grapesjs file manager
            if (in_array($file->getRelativePath(), $this->coreParametersHelper->get('image_path_exclude'))) {
                continue;
            }

            if ($file->isDir()) {
                $files[] = [
                    'type'         => 'directory',
                    'path'         => substr($file->getRealPath(), strrpos($file->getRealPath(), '/') + 1),
                    'absolutePath' => str_replace($originalUploadDir, '', $file->getRealPath()),
                    'icon'         => 'fa fa-folder',
                ];

                continue;
            }

            if ($size = @getimagesize($this->getCompleteFilePath((null !== $subPath && 'root' !== $subPath ? $subPath.'/' : '').$file->getRelativePathname()))) {
                $files[] = [
                    'src'    => $this->getFullUrl((null !== $subPath && 'root' !== $subPath ? $subPath.'/' : '').$file->getRelativePathname()),
                    'width'  => $size[0],
                    'type'   => 'image',
                    'height' => $size[1],
                ];
            } else {
                $files[] = $this->getFullUrl((null !== $subPath && 'root' !== $subPath ? $subPath.'/' : '').$file->getRelativePathname());
            }
        }

        usort($files, function ($item) {
            return 'directory' === ($item['type'] ?? '') ? 0 : 1;
        });

        return $files;
    }

    /**
     * @return array
     */
    public function getDirectories($subPath = null)
    {
        $dirs = [
            ['path' => 'root'],
        ];

        $uploadDir = $this->getUploadDir();

        $finder = new Finder();
        $finder->directories()
            ->in($uploadDir);

        foreach ($finder as $file) {
            // exclude certain folders from grapesjs file manager
            if (in_array($file->getRelativePath(), $this->coreParametersHelper->get('image_path_exclude'))) {
                continue;
            }

            $dirs[] =  [
                'path' => str_replace($uploadDir, '', $file->getRealPath()),
            ];
        }

        return $dirs;
    }

    public function createDirectory($activeDirectory, $newDirectory)
    {
        $fileSystem = new Filesystem();
        $uploadDir  = $this->getUploadDir();

        $targetDirectory = $uploadDir.$activeDirectory;

        if ('root' === $activeDirectory) {
            $targetDirectory = $uploadDir;
        }

        $newDirectory = preg_replace('/[^A-Za-z0-9 ]/', '', $newDirectory);
        $newDirectory = str_replace(' ', '-', $newDirectory);

        if (false !== strrpos($targetDirectory, '/')) {
            $targetDirectory = $targetDirectory.'/';
        }

        $newDirectory = $targetDirectory.$newDirectory;

        if ($fileSystem->exists($newDirectory)) {
            return [
                'status'  => 'error',
                'message' => 'Folder already exists',
            ];
        }

        try {
            $fileSystem->mkdir($newDirectory);
        } catch (IOException $exception) {
            return [
                'status'  => 'error',
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => 'ok',
        ];
    }

    private function removeTrailingSlash(?string $dir): ?string
    {
        if ('/' === substr($dir, -1)) {
            $dir = substr($dir, 0, -1);
        }

        return $dir;
    }
}
