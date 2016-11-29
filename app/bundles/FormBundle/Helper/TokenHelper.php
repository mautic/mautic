<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Model\FormModel;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /**
     * @var
     */
    protected $model;

    /**
     * TokenHelper constructor.
     *
     * @param FormModel $model
     */
    public function __construct(FormModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    public function findFormTokens($content, $clickthrough = [])
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
                if ($form !== null &&
                    (
                        $form->isPublished(false) ||
                        $this->security->hasEntityAccess(
                            'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
                        )
                    )
                ) {
                    $formHtml = ($form->isPublished()) ? $this->formModel->getContent($form) :
                        '<div class="mauticform-error">'.
                        $this->translator->trans('mautic.form.form.pagetoken.notpublished').
                        '</div>';

                    //pouplate get parameters
                    //priority populate value order by: query string (parameters) -> with lead
                    if (!$form->getInKioskMode()) {
                        $this->formModel->populateValuesWithLead($form, $formHtml);
                    }
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
