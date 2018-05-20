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
    /**
     * @param SubmissionEvent $submissionEvent
     * @param array           $tokens
     *
     * @return array
     */
    public function transformTokens(SubmissionEvent $submissionEvent, array $tokens)
    {
        $fields = $submissionEvent->getForm()->getFields();
        foreach ($fields as $field) {
            switch ($field->getType()) {
                case 'file':
                    $tokens["{formfield={$field->getAlias()}}"] = $this->transform(
                        $submissionEvent,
                        $field,
                        $tokens["{formfield={$field->getAlias()}}"]
                    );
                    break;
            }
        }

        return $tokens;
    }
}
