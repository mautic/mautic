<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class FormListType extends AbstractType
{
    private $viewOther;

    private \Mautic\FormBundle\Entity\FormRepository $repo;

    public function __construct(CorePermissions $security, FormModel $model, UserHelper $userHelper)
    {
        $this->viewOther = $security->isGranted('form:forms:viewother');
        $this->repo      = $model->getRepository();

        $this->repo->setCurrentUser($userHelper->getUser());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $viewOther = $this->viewOther;
        $repo      = $this->repo;

        $resolver->setDefaults([
            'choices' => function (Options $options) use ($repo, $viewOther): array {
                $choices = [];

                $forms = $repo->getFormList('', 0, 0, $viewOther, $options['form_type'], $options['top_level'], $options['ignore_ids']);
                foreach ($forms as $form) {
                    $choices[$form['language']]["{$form['name']} ({$form['id']})"] = $form['id'];
                }

                // sort by language then by name
                ksort($choices);
                foreach ($choices as &$group) {
                    ksort($group);
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

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
