<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferenceCenterListType extends AbstractType
{
    /**
     * @var bool
     */
    private $canViewOther = false;

    public function __construct(
        private PageModel $model,
        CorePermissions $corePermissions
    ) {
        $this->canViewOther = $corePermissions->isGranted('page:pages:viewother');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $model        = $this->model;
        $canViewOther = $this->canViewOther;

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) use ($model, $canViewOther): array {
                    $choices = [];
                    $pages   = $model->getRepository()->getPageList('', 0, 0, $canViewOther, $options['top_level'], $options['ignore_ids'], ['isPreferenceCenter']);
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

    public function getParent()
    {
        return ChoiceType::class;
    }
}
