<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FileController extends AjaxController
{
    public const EDITOR_CKEDITOR = 'ckeditor';

    private array $response = [];

    private int $statusCode = Response::HTTP_OK;
    private PathsHelper $pathsHelper;
    private FileUploader $fileUploader;

    /**
     * Uploads a file.
     *
     * @throws FileUploadException
     */
    public function uploadAction(Request $request): JsonResponse
    {
        $editor   = $request->get('editor');
        $mediaDir = $this->getMediaAbsolutePath();
        if (!isset($this->response['error'])) {
            foreach ($request->files as $file) {
                /** @var UploadedFile $file */
                try {
                    $this->fileUploader->validateImage($file);
                    $fileName = $this->fileUploader->upload($mediaDir, $file);
                    $this->successfulResponse($request, $fileName, $editor);
                } catch (FileUploadException) {
                    $this->failureResponse($editor);
                }
            }
        }

        return $this->sendJsonResponse($this->response, $this->statusCode);
    }

    /**
     * List the files in /media directory.
     */
    public function listAction(Request $request): JsonResponse
    {
        $fnames = scandir($this->getMediaAbsolutePath());

        if ($fnames) {
            foreach ($fnames as $name) {
                $imagePath = $this->getMediaAbsolutePath().'/'.$name;
                $imageUrl  = $this->getMediaUrl($request).'/'.$name;
                $imageFile = new File($imagePath, checkPath: false);
                if (!is_dir($name)) {
                    try {
                        $this->fileUploader->validateImage($imageFile);
                        $this->response[] = [
                            'url'   => $imageUrl,
                            'thumb' => $imageUrl,
                            'name'  => $name,
                        ];
                    } catch (FileUploadException) {
                    }
                }
            }
        } else {
            $this->response['error'] = 'Images folder does not exist!';
        }

        return $this->sendJsonResponse($this->response, $this->statusCode, false);
    }

    /**
     * Delete a file from /media directory.
     */
    public function deleteAction(Request $request): JsonResponse
    {
        $src       = InputHelper::clean($request->request->get('src'));
        $imagePath = $this->getMediaAbsolutePath().'/'.basename($src);

        if (!file_exists($imagePath)) {
            $this->response['error'] = 'File does not exist';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        } elseif (!is_writable($imagePath)) {
            $this->response['error'] = 'File is not writable';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        } else {
            unlink($imagePath);
            $this->response['deleted'] = true;
        }

        return $this->sendJsonResponse($this->response, $this->statusCode);
    }

    /**
     * Get the Media directory full file system path.
     *
     * @return string
     */
    public function getMediaAbsolutePath(): string|false
    {
        $mediaDir = realpath($this->pathsHelper->getSystemPath('images', true));
        if (false === $mediaDir) {
            $this->response['error'] = 'Media dir does not exist';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        if (false === is_writable($mediaDir)) {
            $this->response['error'] = 'Media dir is not writable';
            $this->statusCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $mediaDir;
    }

    /**
     * Get the Media directory full file system path.
     */
    public function getMediaUrl(Request $request): string
    {
        return $request->getScheme().'://'
            .$request->getHttpHost()
            .$request->getBasePath().'/'
            .$this->coreParametersHelper->get('image_path');
    }

    private function successfulResponse(Request $request, string $fileName, string $editor): void
    {
        $filePath = $this->getMediaUrl($request).'/'.$fileName;
        if (self::EDITOR_CKEDITOR === $editor) {
            $this->response['uploaded'] = true;
            $this->response['url']      = $filePath;
        } else {
            $this->response['link'] = $filePath;
        }
    }

    private function failureResponse(string $editor): void
    {
        $errorMsg = 'The uploaded image does not have an allowed mime type';
        if (self::EDITOR_CKEDITOR === $editor) {
            $this->response['uploaded']         = false;
            $this->response['error']['message'] = $errorMsg;
        } else {
            $this->response['error'] = $errorMsg;
        }
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireFileController(
        PathsHelper $pathsHelper,
        FileUploader $fileUploader,
    ): void {
        $this->pathsHelper = $pathsHelper;
        $this->fileUploader = $fileUploader;
    }
}
