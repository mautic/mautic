<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PageListType.
 */
class PageListType extends AbstractType
{
    /**
     * @var PageModel
     */
    private $model;

    /**
     * @var bool
     */
    private $canViewOther = false;

    public function __construct(PageModel $pageModel, CorePermissions $corePermissions)
    {
        $this->model        = $pageModel;
        $this->canViewOther = $corePermissions->isGranted('page:pages:viewother');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $model        = $this->model;
        $canViewOther = $this->canViewOther;

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) use ($model, $canViewOther) {
                    $choices = [];
                    $publishedOnly = $options['published_only'] ?? false;
                    $pages = $model->getRepository()->getPageList('', 0, 0, $canViewOther, $options['top_level'], $options['ignore_ids'], [], $publishedOnly);
                    foreach ($pages as $page) {
                        $choices[$page['language']]["{$page['title']} ({$page['id']})"] = $page['id'];
                    }

                    //sort by language
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

        $resolver->setDefined(['top_level', 'ignore_ids', 'published_only']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'page_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
