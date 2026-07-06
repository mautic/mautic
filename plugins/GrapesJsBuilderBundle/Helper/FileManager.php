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
use Symfony\Component\Finder\SplFileInfo;

class FileManager
{
    public const GRAPESJS_IMAGES_DIRECTORY = '';

    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly PathsHelper $pathsHelper,
    ) {
    }

    /**
     * @return array
     *
     * @throws FileUploadException
     */
    public function uploadFiles($request)
    {
        if (isset($request->files->all()['files'])) {
            $files         = $request->files->all()['files'];
            $uploadDir     = $this->getUploadDir();
            $uploadedFiles = [];

            foreach ($files as $file) {
                $this->fileUploader->validateImage($file);
            }

            foreach ($files as $file) {
                $uploadedFiles[] =  $this->getFullUrl($this->fileUploader->upload($uploadDir, $file));
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param string $fileName
     */
    public function deleteFile($fileName): void
    {
        $this->fileUploader->delete($this->getCompleteFilePath($fileName));
    }

    /**
     * @param string $fileName
     */
    public function getCompleteFilePath($fileName): string
    {
        $uploadDir = $this->getUploadDir();

        return $uploadDir.$fileName;
    }

    private function getUploadDir(): string
    {
        return $this->getGrapesJsImagesPath(true);
    }

    public function getFullUrl($fileName, $separator = '/'): string
    {
        // if a static_url (CDN) is configured use that, otherwise use the site url
        $url = $this->coreParametersHelper->get('static_url') ?? $this->coreParametersHelper->get('site_url');

        return $url
            .$separator
            .$this->getGrapesJsImagesPath(false, $separator)
            .$fileName;
    }

    /**
     * @param string $separator
     */
    private function getGrapesJsImagesPath(bool $fullPath = false, $separator = '/'): string
    {
        return $this->pathsHelper->getSystemPath('images', $fullPath)
            .$separator
            .self::GRAPESJS_IMAGES_DIRECTORY;
    }

    /**
     * @deprecated since Mautic 5.2, to be removed in 6.0. Use FileManager::getMediaFiles instead
     */
    public function getImages(): array
    {
        $files      = [];
        $uploadDir  = $this->getUploadDir();

        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($uploadDir)) {
            try {
                $fileSystem->mkdir($uploadDir);
            } catch (IOException) {
                return $files;
            }
        }

        $finder = new Finder();
        $finder->files()->in($uploadDir);

        foreach ($finder as $file) {
            // exclude certain folders from grapesjs file manager
            if (in_array($file->getRelativePath(), $this->coreParametersHelper->get('image_path_exclude'))) {
                continue;
            }

            $filePath = $this->getCompleteFilePath($file->getRelativePathname());
            if ($size = @getimagesize($filePath)) {
                $files[] = [
                    'src'    => $this->getFullUrl($file->getRelativePathname()),
                    'width'  => $size[0],
                    'type'   => 'image',
                    'height' => $size[1],
                ];
            } elseif ('svg' === strtolower($file->getExtension())) {
                $files[] = $this->getSvgFileInfo($filePath, $file->getRelativePathname());
            } else {
                $files[] = $this->getFullUrl($file->getRelativePathname());
            }
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMediaFiles(int $page, int $limit): array
    {
        $files      = [];
        $uploadDir  = $this->getUploadDir();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($uploadDir)) {
            try {
                $fileSystem->mkdir($uploadDir);
            } catch (IOException) {
                return [
                    'data'            => [],
                    'page'            => $page,
                    'limit'           => $limit,
                    'totalItems'      => 0,
                    'totalPages'      => 0,
                    'hasNextPage'     => false,
                    'hasPreviousPage' => false,
                ];
            }
        }

        $finder = new Finder();
        $finder->files()->in($uploadDir)->sortByModifiedTime()->reverseSorting();

        $totalFiles = iterator_count($finder);
        $totalPages = (int) ceil($totalFiles / $limit);

        // Check if the requested page is out of range
        if ($page < 1 || $page > $totalPages) {
            return [
                'data'            => [],
                'page'            => $page,
                'limit'           => $limit,
                'totalItems'      => $totalFiles,
                'totalPages'      => $totalPages,
                'hasNextPage'     => $page < $totalPages,
                'hasPreviousPage' => $page > 1,
            ];
        }

        $offset = ($page - 1) * $limit;

        $filesIterator = new \LimitIterator($finder->getIterator(), $offset, $limit);

        foreach ($filesIterator as $file) {
            if (in_array($file->getRelativePath(), $this->coreParametersHelper->get('image_path_exclude'))) {
                continue;
            }

            $fileInfo = $this->getFileInfo($file);
            if ($fileInfo) {
                $files[] = $fileInfo;
            }
        }

        return [
            'data'            => $files,
            'page'            => $page,
            'limit'           => $limit,
            'totalItems'      => $totalFiles,
            'totalPages'      => $totalPages,
            'hasNextPage'     => $page < $totalPages,
            'hasPreviousPage' => $page > 1,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getFileInfo(SplFileInfo $file): ?array
    {
        $filePath  = $this->getCompleteFilePath($file->getRelativePathname());
        $size      = @getimagesize($filePath);
        $extension = strtolower($file->getExtension());

        if ($size) {
            $info = [
                'src'    => $this->getFullUrl($file->getRelativePathname()),
                'width'  => $size[0],
                'height' => $size[1],
                'type'   => 'image',
            ];
        } elseif ('svg' === $extension) {
            $info = $this->getSvgFileInfo($filePath, $file->getRelativePathname());
        } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
            $info = [
                'src'  => $this->getFullUrl($file->getRelativePathname()),
                'type' => 'document',
            ];
        } else {
            $info = null;
        }

        return $info;
    }

    /**
     * Extract full file metadata from an SVG file.
     *
     * @return array<string, int|string>
     */
    private function getSvgFileInfo(string $filePath, string $relativePathName): array
    {
        $info = [
            'src'    => $this->getFullUrl($relativePathName),
            'width'  => 0,
            'height' => 0,
            'type'   => 'image',
        ];

        $svgContent = @file_get_contents($filePath);
        if ($svgContent) {
            // Suppress XML warnings and enable internal error handling
            $previousErrors = libxml_use_internal_errors(true);

            // Parse SVG with network access disabled to mitigate XXE/SSRF risks
            $svg = simplexml_load_string($svgContent, 'SimpleXMLElement', LIBXML_NONET);

            // Clear any accumulated libxml errors and restore previous setting
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            if ($svg) {
                $svgAttributes = $svg->attributes();

                // Try to get width and height directly
                if (isset($svgAttributes->width) && isset($svgAttributes->height)) {
                    $info['width']  = (int) $svgAttributes->width;
                    $info['height'] = (int) $svgAttributes->height;
                } elseif (isset($svgAttributes->viewBox)) {
                    // Parse the viewBox attribute (format: "x y width height")
                    $viewBox = explode(' ', (string) $svgAttributes->viewBox);
                    if (4 === count($viewBox)) {
                        $info['width']  = (int) $viewBox[2];
                        $info['height'] = (int) $viewBox[3];
                    }
                }
            }
        }

        return $info;
    }
}
