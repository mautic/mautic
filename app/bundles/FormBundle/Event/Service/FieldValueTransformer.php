<?php

namespace Mautic\FormBundle\Event\Service;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FieldValueTransformer
{
    private array $contactFieldsToUpdate = [];

    private array $tokensToUpdate = [];

    private bool $isTransformed = false;

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function transformValuesAfterSubmit(SubmissionEvent $submissionEvent): void
    {
        if (true === $this->isTransformed) {
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
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $tokenAlias = "{formfield={$field->getAlias()}}";

                    if (!empty($tokens[$tokenAlias])) {
                        $this->tokensToUpdate[$tokenAlias] = $tokens[$tokenAlias] = $newValue;
                    }

                    $contactFieldAlias = $field->getMappedField();
                    if ('contact' === $field->getMappedObject() && !empty($contactFieldMatches[$contactFieldAlias])) {
                        $this->contactFieldsToUpdate[$contactFieldAlias] = $contactFieldMatches[$contactFieldAlias] = $newValue;
                    }

                    break;
            }
        }

        $submissionEvent->setTokens($tokens);
        $submissionEvent->setContactFieldMatches($contactFieldMatches);
        $this->isTransformed = true;
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
     * @deprecated will be removed in Mautic 4. This should have been a private method. Not actually needed.
     */
    public function isIsTransformed(): bool
    {
        return $this->isTransformed;
    }
}
