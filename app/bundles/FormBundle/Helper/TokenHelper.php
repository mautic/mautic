<?php

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Model\FormModel;

class TokenHelper
{
    protected \Mautic\FormBundle\Model\FormModel $formModel;

    protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security;

    public function __construct(FormModel $formModel, CorePermissions $security)
    {
        $this->formModel = $formModel;
        $this->security  = $security;
    }

    public function findFormTokens($content): array
    {
        $tokens = [];

        preg_match_all('/{form=(.*?)}/', $content, $matches);

        if (count($matches[0])) {
            foreach ($matches[1] as $k => $id) {
                $token = $matches[0][$k];

                if (isset($tokens[$token])) {
                    continue;
                }
                $form = $this->formModel->getEntity($id);
                if (null !== $form &&
                    (
                        $form->isPublished(false) ||
                        $this->security->hasEntityAccess(
                            'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
                        )
                    )
                ) {
                    $formHtml = ($form->isPublished()) ? $this->formModel->getContent($form) :
                        '';

                    // pouplate get parameters
                    $this->formModel->populateValuesWithGetParameters($form, $formHtml);

                    $tokens[$token] = $formHtml;
                } else {
                    $tokens[$token] = '';
                }
            }
        }

        return $tokens;
    }
}
