<?php
namespace Mautic\ScoringBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of ScoringCategoryListType
 *
 * @author captivea-qch
 */
class ScoringCategoryListType extends AbstractType {
    private $choices = [];

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $choices = $factory->getModel('scoring.scoringCategory')->getRepository()->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 's.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
            ],
        ]);

        foreach ($choices as $choice) {
            $this->choices[$choice->getId()] = $choice->getName(true);
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices'     => $this->choices,
            'empty_value' => false,
            'expanded'    => false,
            'multiple'    => true,
            'required'    => false,
            'empty_value' => 'mautic.core.form.chooseone',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'scoringcategory_list';
    }

    public function getParent()
    {
        return 'choice';
    }
}
