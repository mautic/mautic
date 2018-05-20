<?php
/**
 * Created by PhpStorm.
 * User: zdeno
 * Date: 19. 5. 2018
 * Time: 14:59.
 */

namespace Mautic\FormBundle\Event\Service;

use Mautic\FormBundle\Event\SubmissionEvent;

class FieldTokenTransformer extends FieldValueTransformer
{
    private $fieldValueTransformer;

    public function __construct()
    {
        $this->fieldValueTransformer = new FieldValueTransformer();
    }

    public function transformTokens(SubmissionEvent $submissionEvent, $tokens)
    {
        if ($submissionEvent->getSubmission()->getId()) {
            $fields = $submissionEvent->getForm()->getFields();
            foreach ($fields as $field) {
                switch ($field->getType()) {
                    case 'file':
                        $tokens["{formfield={$field->getAlias()}}"] = $this->fieldValueTransformer->get(
                            $submissionEvent,
                            $field,
                            $tokens["{formfield={$field->getAlias()}}"]
                        );
                        break;
                }
            }
        }
        return $tokens;
    }
}
