<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

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
        $mediaDirRaw = $this->container->getParameter('kernel.root_dir').'/../'.$this->factory->getParameter('image_path');
        $mediaDir = realpath($mediaDirRaw);

        if ($mediaDir === false) {
            // @todo media dir does not exist
        }

        foreach ($this->request->files as $file) {
            // @todo check file extension
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move($mediaDir, $fileName);
        }

        return $this->sendJsonResponse(
            array(
                'link' => $this->request->getScheme() . '://' . $this->request->getHttpHost() . $this->request->getBasePath().'/'.$this->factory->getParameter('image_path').'/'.$fileName
            )
        );
    }
}
