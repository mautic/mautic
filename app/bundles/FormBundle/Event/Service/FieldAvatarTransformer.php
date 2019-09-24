<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event\Service;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;

class FieldAvatarTransformer
{
    /**
     * @var AvatarHelper
     */
    private $avatarHelper;

    /**
     * @var FormUploader
     */
    private $uploader;

    /**
     * @var Field
     */
    private $field;

    public function __construct(AvatarHelper $avatarHelper, FormUploader $uploader)
    {
        $this->avatarHelper = $avatarHelper;
        $this->uploader     = $uploader;
    }

    public function transformAvatarFromField(Field $field)
    {
        $this->field = $field;
        $this->isProfileImage();
    }

    private function isProfileImage()
    {
        if (!ArrayHelper::getValue(FormFieldFileType::PROPERTY_PREFERED_PROFILE_IMAGE, $this->field->getProperties())) {
            throw new \LogicException();
        }
    }
}
