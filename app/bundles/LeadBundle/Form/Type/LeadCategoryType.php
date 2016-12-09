<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CategoryBundle\Model\CategoryModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadCategoryType.
 */
class LeadCategoryType extends AbstractType
{
    private $categoryModel;

    /**
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $model = $this->categoryModel;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($model) {
                $categories = $model->getLookupResults('global');

                $choices = [];
                foreach ($categories as $cat) {
                    $choices[$cat['id']] = $cat['title'];
                }

                return $choices;
            },
            'global_only' => true,
            'required'    => false,
        ]);
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadcategory_choices';
    }
}
