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

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\AssetEvent;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PublicController.
 */
class PublicController extends CommonFormController
{
    /**
     * @param string $slug
     *
     * @return Response
     */
    public function downloadAction($slug)
    {
        //find the asset
        $security = $this->get('mautic.security');

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $this->getModel('asset');

        /** @var \Mautic\AssetBundle\Entity\Asset $entity */
        $entity = $model->getEntityBySlugs($slug);

        if (!empty($entity)) {
            $published = $entity->isPublished();

            //make sure the asset is published or deny access if not
            if ((!$published) && (!$security->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()))) {
                $model->trackDownload($entity, $this->request, 401);

                return $this->accessDenied();
            }

            //make sure URLs match up
            $url        = $model->generateUrl($entity, false);
            $requestUri = $this->request->getRequestUri();
            //remove query
            $query = $this->request->getQueryString();

            if (!empty($query)) {
                $requestUri = str_replace("?{$query}", '', $url);
            }

            //redirect if they don't match
            if ($requestUri != $url) {
                $model->trackDownload($entity, $this->request, 301);

                return $this->redirect($url, 301);
            }

            //all the checks pass so provide the asset for download
            // @deprecated 2.0 - to be removed in 3.0
            $dispatcher = $this->get('event_dispatcher');
            if ($dispatcher->hasListeners(AssetEvents::ASSET_ON_DOWNLOAD)) {
                $event = new AssetEvent($entity);
                $dispatcher->dispatch(AssetEvents::ASSET_ON_DOWNLOAD, $event);
            }

            if ($entity->isRemote()) {
                $model->trackDownload($entity, $this->request, 200);

                // Redirect to remote URL
                $response = new RedirectResponse($entity->getRemotePath());
            } else {
                try {
                    //set the uploadDir
                    $entity->setUploadDir($this->get('mautic.helper.core_parameters')->getParameter('upload_dir'));
                    $contents = $entity->getFileContents();
                    $model->trackDownload($entity, $this->request, 200);
                } catch (\Exception $e) {
                    $model->trackDownload($entity, $this->request, 404);

                    return $this->notFound();
                }

                $response = new Response();

                if ($entity->getDisallow()) {
                    $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
                }

                $response->headers->set('Content-Type', $entity->getFileMimeType());

                $stream = $this->request->get('stream', 0);
                if (!$stream) {
                    $response->headers->set('Content-Disposition', 'attachment;filename="'.$entity->getOriginalFileName());
                }
                $response->setContent($contents);
            }

            return $response;
        }

        $model->trackDownload($entity, $this->request, 404);

        return $this->notFound();
    }
}
