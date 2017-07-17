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

class UploadController extends DropzoneController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function upload()
    {
        $request  = $this->container->get('request');
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
