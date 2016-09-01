<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;


use Mautic\FormBundle\Model\FieldModel;

trait FormFieldTrait
{
    /**
     * @var FieldModel
     */
    protected $fieldModel;

    /**
     * @param FieldModel $fieldModel
     */
    public function setFieldModel(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * @param      $formId
     * @param bool $asTokens
     *
     * @return array
     */
    protected function getFormFields($formId, $asTokens = true)
    {
        $fields = $this->fieldModel->getSessionFields($formId);

        $choices = [];

        foreach ($fields as $f) {
            if (in_array($f['type'], ['button', 'freetext', 'captcha'])) {
                continue;
            }

            $choices[($asTokens) ? '{formfield='.$f['alias'].'}' : $f['alias']] = $f['label'];
        }

        return $choices;
    }
}