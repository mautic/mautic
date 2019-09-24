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
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Form\Type\FormFieldFileType;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;
use Symfony\Component\Routing\RouterInterface;

class FieldValueTransformer
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $contactFieldsToUpdate = [];

    /**
     * @var array
     */
    private $tokensToUpdate = [];

    /**
     * @var bool
     */
    private $isTransformed = false;

    /**
     * @var AvatarHelper
     */
    private $avatarHelper;

    /**
     * @var FormUploader
     */
    private $uploader;

    /**
     * FieldValueTransformer constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, AvatarHelper $avatarHelper, FormUploader $uploader)
    {
        $this->router       = $router;
        $this->avatarHelper = $avatarHelper;
        $this->uploader     = $uploader;
    }

    public function transformValuesAfterSubmit(SubmissionEvent $submissionEvent)
    {
        if ($this->isIsTransformed()) {
            return;
        }
        $fields              = $submissionEvent->getForm()->getFields();
        $contactFieldMatches = $submissionEvent->getContactFieldMatches();
        $tokens              = $submissionEvent->getTokens();

        /** @var Field $field */
        foreach ($fields as $field) {
            switch ($field->getType()) {
                case 'file':

                    $newValue = $this->router->generate(
                        'mautic_form_file_download',
                        [
                            'submissionId' => $submissionEvent->getSubmission()->getId(),
                            'field'        => $field->getAlias(),
                        ],
                        true
                    );

                    $tokenAlias = "{formfield={$field->getAlias()}}";

                    if (!empty($tokens[$tokenAlias])) {
                        $this->tokensToUpdate[$tokenAlias] = $tokens[$tokenAlias] = $newValue;
                    }

                    $contactFieldAlias = $field->getLeadField();
                    if (!empty($contactFieldMatches[$contactFieldAlias])) {
                        $this->contactFieldsToUpdate[$contactFieldAlias] = $contactFieldMatches[$contactFieldAlias] = $newValue;
                    }

                    if (ArrayHelper::getValue(FormFieldFileType::PROPERTY_PREFERED_PROFILE_IMAGE, $field->getProperties())) {
                        try {
                            ///$this->uploader->getCompleteFilePath($field, )
                            //  $this->avatarHelper->setAvatarFromFile($submissionEvent->getLead(), uploadFile)
                            $this->contactFieldsToUpdate['preferred_profile_image'] = 'custom';
                        } catch (\Exception $exception) {
                        }
                    }

                    break;
            }
        }

        $submissionEvent->setTokens($tokens);
        $submissionEvent->setContactFieldMatches($contactFieldMatches);
        $this->isIsTransformed();
    }

    /**
     * @return array
     */
    public function getContactFieldsToUpdate()
    {
        return $this->contactFieldsToUpdate;
    }

    /**
     * @return array
     */
    public function getTokensToUpdate()
    {
        return $this->tokensToUpdate;
    }

    /**
     * @return bool
     */
    public function isIsTransformed()
    {
        return $this->isTransformed;
    }
}
