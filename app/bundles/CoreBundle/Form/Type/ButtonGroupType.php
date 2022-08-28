<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ButtonGroupType.
 */
class ButtonGroupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'expanded'           => true,
            'multiple'           => false,
            'placeholder'        => false,
            'required'           => false,
            'label_attr'         => ['class' => 'control-label'],
            'button_group_class' => 'btn-block',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['buttonBlockClass'] = $options['button_group_class'];
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'button_group';
    }
}
