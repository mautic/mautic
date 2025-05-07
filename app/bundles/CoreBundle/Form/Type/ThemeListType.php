<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ThemeListType extends AbstractType
{
    public function __construct(
        private ThemeHelperInterface $themeHelper,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'           => function (Options $options): array {
                    $themes = $this->themeHelper->getInstalledThemes($options['feature']);
                    if ($options['include_code_mode']) {
                        $themes['mautic_code_mode'] = 'Code Mode';
                    }

                    return array_flip($themes);
                },
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.core.form.theme',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'required'          => false,
                'attr'              => ['class' => 'form-control'],
                'feature'           => 'all',
                'include_code_mode' => true,
            ]
        );
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
