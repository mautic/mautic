<?php
/*
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Uploader;

use Mautic\CoreBundle\Uploader\AbstractUploader;

class LeadNoteUploader extends AbstractUploader
{
    /**
     * Upload directory, for example media/files/note/1 ...
     *
     * @return string
     */
    public function getUploadDirectory()
    {
        return ['files', 'note', $this->getEntity()->getId()];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return ['attachment'];
    }

    /**
     * @return array
     */
    public function getForm()
    {
        return 'leadnote';
    }
}
