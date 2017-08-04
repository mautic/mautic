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
    public function __construct(MauticFactory $factory) {
        // we need to find a way that this filter is applied only if no value is there.
        // either there is some undocumented way or hidden thing to pass/get options like entity through controller to form builder then form type
        // either there is none; we don't have time to deal with some strange behaviour asked to "display an option that should never be displayd"
        
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
            if(!$choice->getIsGlobalScore()) { // tmp
                $this->choices[$choice->getId()] = $choice->getName(true);
            }
        }
        
        // so, yeah, we're doing ALL CIRCLE DIRTY
        $uri = $factory->getRequest()->getPathInfo();
        $matches = array();
        if(preg_match('`/points/edit/([0-9]+)$`iU', $uri, $matches)) {
            $chosenId = intval($matches[1]);
            $point = $factory->getEntityManager()->getRepository('MauticPointBundle:Point')->find($chosenId);
            $scoringCategory = $point->getScoringCategory();
            if(!empty($scoringCategory) && !array_key_exists($scoringCategory->getId(), $this->choices)) {
                $this->choices[$scoringCategory->getId()] = $scoringCategory->getName();
            }
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
            'empty_value' => 'mautic.scoring.scoringCategory.globalscore.name',
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
