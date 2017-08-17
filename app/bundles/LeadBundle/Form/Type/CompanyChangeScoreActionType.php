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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;

/* CAPTIVEA.CORE START **/
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/** CAPTIVEA.CORE END **/

/**
 * Class CompanyChangeScoreActionType.
 */
class CompanyChangeScoreActionType extends AbstractType
{
    /** CAPTIVEA.CORE START **/

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
        $choices = [];
        $r       = $this->entityManager->getRepository('MauticScoringBundle:ScoringCategory')->findBy(['isPublished' => true]);
        foreach ($r as $l) {
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
                'data'       => (empty($options['data']['scoringCategory'])) ? null : (is_object($options['data']['scoringCategory']) ? $options['data']['scoringCategory']->getId() : $options['data']['scoringCategory']),
            ]
        );
        /* CAPTIVEA.CORE END **/
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'scorecontactscompanies_action';
    }
}
