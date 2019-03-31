<?php
/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Uploader;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\MauticLeadBundle\Uploader\Decorator\LeadNoteDecorator;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class LeadNotesUploader
{
    const FIELDS = ['attachment'];

    /** @var FileUploader */
    private $fileUploader;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /**
     * LeadNotesUploader constructor.
     *
     * @param FileUploader $fileUploader
     */
    public function __construct(
        FileUploader $fileUploader
    ) {
        $this->fileUploader         = $fileUploader;
        $this->propertyAccessor     = new PropertyAccessor();
    }

    /**
     * @param LeadNoteDecorator $leadNoteDecorator
     * @param LeadNote          $leadNote
     */
    public function uploadFiles(LeadNoteDecorator $leadNoteDecorator, LeadNote $leadNote)
    {
        $files = [];
        if (isset($leadNoteDecorator->getRequest()->files->all()['notes'])) {
            $files = $leadNoteDecorator->getRequest()->files->all()['notes'];
        }

        foreach (self::FIELDS as $field) {
            // nothing for upload
            if (empty($files[$field])) {
                continue;
            }

            $file = $files[$field];

            try {
                $uploadedFile = $this->fileUploader->upload($leadNoteDecorator->getUploadPathDirectory(), $file);
                $this->propertyAccessor->setValue($leadNote, $field, $uploadedFile);
            } catch (FileUploadException $e) {
            }
        }
    }
}
