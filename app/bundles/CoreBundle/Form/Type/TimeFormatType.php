<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class TimeFormatType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                '24-'.$this->translator->trans('mautic.core.time.hour') => '24',
                '12-'.$this->translator->trans('mautic.core.time.hour') => '12',
            ],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.core.type.time_format',
            'label_attr'  => ['class' => ''],
            'empty_value' => false,
            'required'    => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
