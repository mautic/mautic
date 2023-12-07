<?php

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FocusListType extends AbstractType
{
    private $repo;

    public function __construct(protected \MauticPlugin\MauticFocusBundle\Model\FocusModel $focusModel)
    {
        $this->repo       = $this->focusModel->getRepository();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options): array {
                    $choices = [];

                    $list = $this->repo->getFocusList($options['data']);
                    foreach ($list as $row) {
                        $choices[$row['name']] = $row['id'];
                    }

                    // sort by language
                    ksort($choices, SORT_NATURAL);

                    return $choices;
                },
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'placeholder' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.focus.no.focusitem.note' : 'mautic.core.form.chooseone';
                },
                'disabled' => function (Options $options): bool {
                    return empty($options['choices']);
                },
                'top_level'      => 'variant',
                'variant_parent' => null,
                'ignore_ids'     => [],
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
