<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @extends AbstractType<mixed>
 */
class LookupType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'];

        if (!is_array($attr)) {
            $attr = [];
        }

        $view->vars['attr'] = array_merge([
            'data-toggle' => 'field-lookup',
            'data-action' => 'lead:fieldList',
        ], $attr);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
