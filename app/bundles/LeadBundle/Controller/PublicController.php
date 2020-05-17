<?php
/*
 *
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *  * @link        http://mautic.org
 *  *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\LeadBundle\Model\NoteModel;
use Mautic\LeadBundle\Uploader\LeadNoteUploader;

class PublicController extends AbstractFormController
{
    /**
     * @param $objectId
     *
     * @return array|bool|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function downloadAction($objectId)
    {
        //find the asset
        $security = $this->get('mautic.security');

        /** @var NoteModel $model */
        $model = $this->getModel('lead.note');

        /** @var LeadNote $entity */
        $entity = $model->getEntity($objectId);

        if (!empty($entity)) {
            //make sure the asset is published or deny access if not
            if (!$security->hasEntityAccess('lead:note:viewown', 'lead:note:viewother', $entity->getCreatedBy())) {
                return $this->accessDenied();
            }
            /** @var LeadNoteUploader $leadNoteUploaderDecorator */
            $leadNoteUploaderDecorator = $this->get('mautic.lead.note.uploader');
            try {
                return $leadNoteUploaderDecorator->downloadFile($entity, 'attachment');
            } catch (FileNotFoundException $exception) {
                return $this->notFound();
            }
        }

        return $this->notFound();
    }
}
