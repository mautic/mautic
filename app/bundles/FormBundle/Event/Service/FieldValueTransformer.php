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

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;
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
     * FieldValueTransformer constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
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
