<?php
/**
 * Created by PhpStorm.
 * User: zdeno
 * Date: 19. 5. 2018
 * Time: 14:59.
 */

namespace Mautic\FormBundle\Event\Service;

use Mautic\FormBundle\Event\SubmissionEvent;

class SubmissionTokensProcessService
{
    public function process(SubmissionEvent $submissionEvent, $tokens)
    {
        if ($submissionEvent->getSubmission()->getId()) {
            $fields = $submissionEvent->getForm()->getFields();
            foreach ($fields as $field) {
                switch ($field->getType()) {
                    case 'file':
                        $tokens["{formfield={$field->getType()}}"] = $submissionEvent->getRouter()->generate(
                            'mautic_form_file_download',
                            [
                                'submissionId' => $submissionEvent->getSubmission()->getId(),
                                'field'        => $field->getAlias(),
                            ],
                            true
                        );
                        break;
                }
            }
        }

        return $tokens;
    }
}
