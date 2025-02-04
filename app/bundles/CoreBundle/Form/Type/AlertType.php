<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlertType extends AbstractType
{
    /**
     * @param FormInterface<FormInterface> $form
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['message']     = $options['message'];
        $view->vars['messageType'] = $options['message_type'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'label'         => false,
                'mapped'        => false,
                'required'      => false,
                'message_type'  => 'info',
            ]
        );

        $resolver->setRequired(['message']);
    }
}
