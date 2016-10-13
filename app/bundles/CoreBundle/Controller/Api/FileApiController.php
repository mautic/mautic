<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FileApiController.
 */
class FileApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        // $this->model            = $this->getModel('campaign');
        // $this->entityClass      = 'Mautic\CampaignBundle\Entity\Campaign';
        $this->entityNameOne   = 'file';
        $this->entityNameMulti = 'files';
        // $this->permissionBase   = 'campaign:campaigns';
        // $this->serializerGroups = array("campaignDetails", "categoryList", "publishDetails");
    }

    protected $imageMimes = [
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
    ];

    protected $statusCode = Response::HTTP_OK;

    /**
     * Uploads a file.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createAction($dir)
    {
        $path = $this->getAbsolutePath($dir);
        if (!isset($this->response['error'])) {
            foreach ($this->request->files as $file) {
                if (in_array($file->getMimeType(), $this->imageMimes)) {
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move($path, $fileName);
                    $this->response['link'] = $this->getMediaUrl().'/'.$fileName;
                } else {
                    $this->response['error'] = 'The uploaded image does not have an allowed mime type';
                }
            }
        }

        return $this->sendJsonResponse($this->response, $this->statusCode);
    }

    /**
     * List the files in /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction($dir)
    {
        $fnames = scandir($this->getAbsolutePath($dir));

        if (is_array($fnames)) {
            foreach ($fnames as $key => $name) {
                // remove hidden files
                if (substr($name, 0, 1) === '.') {
                    unset($fnames[$key]);
                }
            }
        } else {
            return $this->returnError(ucfirst($dir).' dir is not readable', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $view = $this->view([$this->entityNameMulti => $fnames]);

        return $this->handleView($view);
    }

    /**
     * Delete a file from /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction()
    {
        $src       = InputHelper::clean($this->request->request->get('src'));
        $response  = ['deleted' => false];
        $imagePath = $this->getAbsolutePath().'/'.basename($src);

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
    protected function getAbsolutePath($dir)
    {
        $possibleDirs = ['assets', 'images'];
        $dir          = InputHelper::alphanum($dir);

        if (!in_array($dir, $possibleDirs)) {
            return $this->notFound($dir.' not found. Only '.implode(' or ', $possibleDirs).' options are possible.');
        }

        $absoluteDir = realpath($this->get('mautic.helper.paths')->getSystemPath($dir, true));

        if ($absoluteDir === false) {
            return $this->returnError('Media dir does not exist', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (is_writable($absoluteDir) === false) {
            return $this->returnError('Media dir is not writable', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $subdir = trim(InputHelper::alphanum($this->request->get('subdir', ''), true, false, ['\/']), '/');
        $path   = $absoluteDir.'/'.$subdir;

        if (!file_exists($path)) {
            return $this->notFound($subdir.' doesn\'t exist in the '.$dir.' dir.');
        }

        return $path;
    }

    /**
     * Get the Media directory full file system path.
     *
     * @return string
     */
    protected function getMediaUrl()
    {
        return $this->request->getScheme().'://'
            .$this->request->getHttpHost()
            .$this->request->getBasePath().'/'
            .$this->factory->getParameter('image_path');
    }
}
