<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\FormRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class FormListType extends AbstractType
{
    private readonly bool $viewOther;

    public function __construct(
        CorePermissions $security,
        UserHelper $userHelper,
        private readonly FormRepository $formRepository,
    ) {
        $this->viewOther = $security->isGranted('form:forms:viewother');

        $this->formRepository->setCurrentUser($userHelper->getUser());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $viewOther = $this->viewOther;

        $resolver->setDefaults([
            'choices' => function (Options $options) use ($viewOther): array {
                $choices = [];

                $forms = $this->formRepository->getFormList('', 0, 0, $viewOther, $options['form_type'], $options['top_level'], $options['ignore_ids']);
                foreach ($forms as $form) {
                    $choices[$form['language']]["{$form['name']} ({$form['id']})"] = $form['id'];
                }

                // sort by language then by name
                ksort($choices);
                foreach ($choices as $language => $group) {
                    ksort($group);
                    $choices[$language] = $group;
                }

                return $choices;
            },
            'expanded'          => false,
            'multiple'          => true,
            'placeholder'       => false,
            'form_type'         => null,
            'top_level'         => null,
            'ignore_ids'        => [],
        ]);

        $resolver->setDefined(['form_type', 'top_level', 'ignore_ids']);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
