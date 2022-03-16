<?php

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Model\FormModel;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * @var CorePermissions
     */
    protected $security;

    /**
     * TokenHelper constructor.
     */
    public function __construct(FormModel $formModel, CorePermissions $security)
    {
        $this->formModel = $formModel;
        $this->security  = $security;
    }

    /**
     * @param $content
     *
     * @return array
     */
    public function findFormTokens($content)
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
                    $formHtml = ($form->isPublished()) ? $this->formModel->getContent($form, false) :
                        '';

                    //pouplate get parameters
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
