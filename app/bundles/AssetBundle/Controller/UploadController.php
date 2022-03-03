<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Oneup\UploaderBundle\Controller\DropzoneController;
use Oneup\UploaderBundle\Uploader\Response\EmptyResponse;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends DropzoneController
{
    public function upload(): JsonResponse
    {
        /** @var Request $request */
        $request  = $this->container->get('request_stack')->getCurrentRequest();
        $response = new EmptyResponse();
        $files    = $this->getFiles($request->files);

        if (!empty($files)) {
            foreach ($files as $file) {
                try {
                    $this->handleUpload($file, $response, $request);
                } catch (UploadException $e) {
                    $this->errorHandler->addException($response, $e);
                } catch (\Exception $e) {
                    error_log($e);

                    $error = new UploadException($this->container->get('translator')->trans('mautic.asset.error.file.failed'));
                    $this->errorHandler->addException($response, $error);
                }
            }
        } else {
            $error = new UploadException($this->container->get('translator')->trans('mautic.asset.error.file.failed'));
            $this->errorHandler->addException($response, $error);
        }

        return $this->createSupportedJsonResponse($response->assemble());
    }
}
