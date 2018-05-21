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
