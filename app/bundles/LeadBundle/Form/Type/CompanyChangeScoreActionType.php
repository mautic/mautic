<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/** CAPTIVEA.CORE START **/
use Mautic\CoreBundle\Factory\MauticFactory;
/** CAPTIVEA.CORE END **/

/**
 * Class CompanyChangeScoreActionType.
 */
class CompanyChangeScoreActionType extends AbstractType
{
    /** CAPTIVEA.CORE START **/
    
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }
    
    /** CAPTIVEA.CORE END **/
    
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'score',
            'number',
            [
                'label'       => 'mautic.lead.lead.events.changecompanyscore',
                'attr'        => ['class' => 'form-control'],
                'label_attr'  => ['class' => 'control-label'],
                'precision'   => 0,
                'data'        => (isset($options['data']['score'])) ? $options['data']['score'] : 0,
                'constraints' => [
                    new NotEqualTo(
                        [
                            'value'   => 0,
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );
        
        /** CAPTIVEA.CORE START **/
        $choices = array();
        $r = $this->factory->getEntityManager()->getRepository('MauticScoringBundle:ScoringCategory')->findBy(array('isPublished' => true));
        foreach($r as $l) {
            $choices[$l->getId()] = $l->getName();
        }
        $builder->add(
            'scoringCategory',
            'choice',
            [
                'label'      => 'mautic.campaign.form.type.scoringCategory',
                'attr'       => ['class' => 'form-control'],
                'label_attr' => ['class' => 'control-label'],
                'choices'    => $choices,
                'data' => (empty($options['data']['scoringCategory'])) ? null : (is_object($options['data']['scoringCategory'])? $options['data']['scoringCategory']->getId():$options['data']['scoringCategory']),
            ]
        );
        /** CAPTIVEA.CORE END **/
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'scorecontactscompanies_action';
    }
}
