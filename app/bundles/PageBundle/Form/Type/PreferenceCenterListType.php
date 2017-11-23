<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PageListType.
 */
class PreferenceCenterListType extends AbstractType
{
    /**
     * @var \Mautic\PageBundle\Model\PageModel
     */
    private $model;

    /**
     * @var bool
     */
    private $canViewOther = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->model        = $factory->getModel('page');
        $this->canViewOther = $factory->getSecurity()->isGranted('page:pages:viewother');
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
                    $pages = $model->getRepository()->getPageList('', 0, 0, $canViewOther, $options['top_level'], $options['ignore_ids'], ['isPreferenceCenter']);
                    foreach ($pages as $page) {
                        if ($page['isPreferenceCenter']) {
                            $choices[$page['language']][$page['id']] = "{$page['title']} ({$page['id']})";
                        }
                    }

                    //sort by language
                    ksort($choices);

                    foreach ($choices as $lang => &$pages) {
                        ksort($pages);
                    }

                    return $choices;
                },
                'empty_value' => false,
                'expanded'    => false,
                'multiple'    => true,
                'required'    => false,
                'top_level'   => 'variant',
                'ignore_ids'  => [],
            ]
        );

        $resolver->setDefined(['top_level', 'ignore_ids']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'preference_center_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
