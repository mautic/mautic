<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private CorePermissions $corePermissions,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $attr = ['data-placeholder' => $this->translator->trans('mautic.project.mautic.project.select')];

        if ($this->corePermissions->isGranted(ProjectPermissions::CAN_CREATE)) {
            $attr['data-placeholder']     = $this->translator->trans('mautic.project.select_or_create');
            $attr['data-action']          = 'createProject';
            $attr['data-no-results-text'] = $this->translator->trans('mautic.project.enter_to_create');
            $attr['data-allow-add']       = 'true';
        }

        $resolver->setDefaults(
            [
                'label'                => 'project.menu.index',
                'class'                => Project::class,
                'query_builder'        => fn (EntityRepository $er) => $er->createQueryBuilder('p')->orderBy('p.name', 'ASC'),
                'choice_label'         => 'name',
                'multiple'             => true,
                'required'             => false,
                'disabled'             => !$this->corePermissions->isGranted(ProjectPermissions::CAN_ASSOCIATE),
                'by_reference'         => false,
                'attr'                 => $attr,
            ]
        );
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
