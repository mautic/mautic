<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CategoryBundle\Model\CategoryModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class LeadCategoryType extends AbstractType
{
    public function __construct(
        private CategoryModel $categoryModel
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'           => function (Options $options): array {
                $categories = $this->categoryModel->getLookupResults('global');
                $choices    = [];

                foreach ($categories as $cat) {
                    $choices[$cat['title']] = $cat['id'];
                }

                return $choices;
            },
            'global_only' => true,
            'required'    => false,
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadcategory_choices';
    }
}
