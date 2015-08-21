<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class MergeType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class MergeType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$builder->addEventSubscriber(new CleanFormSubscriber());
        //$builder->addEventSubscriber(new FormExitSubscriber('lead.lead', $options));

            $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer(
                $this->factory->getEntityManager(),
                'MauticUserBundle:User'
            );

            $leadChoices = array(
            );

            foreach ($options['data'] as $l) {
                 $leadChoices[$l->getId()] = $l->getPrimaryIdentifier();
             }

            $builder->add(
                'lead_to_merge',
                'choice',
                array(
                    'choices'    => $leadChoices,
                    'label'      => $this->factory->getTranslator()->trans('mautic.lead.merge.select'),
                    'label_attr' => array('class' => 'control-label'),
                    'multiple'   => false,
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'constraints' => array(
                            new NotBlank(
                                array(
                                    'message' => 'mautic.core.value.required'
                                )
                            )
                        )
                )
            );

            $builder->add('buttons', 'form_buttons', array(
            'container_class' => 'lead-merge-buttons',
            'apply_text'      => false,
            'save_text'       => 'mautic.core.form.save'
        ));

        

        
        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'  => 'Mautic\LeadBundle\Entity\Lead',
            )
        );

    }

    /**
     * @return string
     */
    public function getName()
    {
        return "lead_merge";
    }
}
