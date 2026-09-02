<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PreferenceCenterListType extends AbstractType
{
    private readonly bool $canViewOther;

    public function __construct(
        CorePermissions $corePermissions,
        private readonly \Mautic\PageBundle\Entity\PageRepository $pageRepository,
    ) {
        $this->canViewOther = $corePermissions->isGranted('page:pages:viewother');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $canViewOther = $this->canViewOther;

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) use ($canViewOther): array {
                    $choices = [];
                    $pages   = $this->pageRepository->getPageList('', 0, 0, $canViewOther, $options['top_level'], $options['ignore_ids'], ['isPreferenceCenter']);
                    foreach ($pages as $page) {
                        if ($page['isPreferenceCenter']) {
                            $choices[$page['language']]["{$page['title']} ({$page['id']})"] = $page['id'];
                        }
                    }

                    // sort by language
                    ksort($choices);

                    foreach ($choices as &$pages) {
                        ksort($pages);
                    }

                    return $choices;
                },
                'placeholder'       => false,
                'expanded'          => false,
                'multiple'          => true,
                'required'          => false,
                'top_level'         => 'variant',
                'ignore_ids'        => [],
            ]
        );

        $resolver->setDefined(['top_level', 'ignore_ids']);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
