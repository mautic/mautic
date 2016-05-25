<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class FileController
 */
class FileController extends AjaxController
{
    /**
     * Uploads a file
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uploadAction()
    {
        $mediaDir = $this->getMediaAbsolutePath();

        foreach ($this->request->files as $file) {
            // @todo check file extension
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move($mediaDir, $fileName);
        }

        return $this->sendJsonResponse(
            array(
                'link' => $this->getMediaUrl().'/'.$fileName
            )
        );
    }

    /**
     * List the files in /media directory
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction()
    {
        $fnames   = scandir($this->getMediaAbsolutePath());
        $response = array();
        $mimes    = array(
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png'
        );

        if ($fnames) {
            foreach ($fnames as $name) {
                $imagePath = $this->getMediaAbsolutePath().'/'.$name;
                $imageUrl = $this->getMediaUrl().'/'.$name;
                if (!is_dir($name) && in_array(mime_content_type($imagePath), $mimes)) {
                    $response[] = array(
                        'url'   => $imageUrl,
                        'thumb' => $imageUrl,
                        'name'  => $name
                    );
                }
            }
        } else {
            $response['error'] = 'Images folder does not exist!';
        }

        return $this->sendJsonResponse($response, false);
    }

    /**
     * Delete a file from /media directory
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction()
    {
        $src       = InputHelper::clean($this->request->request->get('src'));
        $response  = array('deleted' => false);
        $imagePath = $this->getMediaAbsolutePath().'/'.basename($src);

        if (!file_exists($imagePath)) {
            $response['error'] = 'File does not exist';
        } elseif (!is_writable($imagePath)) {
            $response['error'] = 'File is not writable';
        } else {
            unlink($imagePath);
            $response['deleted'] = true;
        }

        return $this->sendJsonResponse($response);
    }


    /**
     * Get the Media directory full file system path
     *
     * @return string
     */
    public function getMediaAbsolutePath()
    {
        $mediaDirRaw = $this->container->getParameter('kernel.root_dir').'/../'.$this->factory->getParameter('image_path');
        $mediaDir = realpath($mediaDirRaw);

        if ($mediaDir === false) {
            // @todo media dir does not exist
        }

        return $mediaDir;
    }

    /**
     * Get the Media directory full file system path
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->request->getScheme().'://'
            .$this->request->getHttpHost()
            .$this->request->getBasePath().'/'
            .$this->factory->getParameter('image_path');
    }
}
