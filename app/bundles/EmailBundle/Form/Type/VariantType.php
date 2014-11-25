<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class VariantType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class VariantType extends AbstractType
{

    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('weight', 'integer', array(
            'label'      => 'mautic.email.form.trafficweight',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.trafficweight.help'
            ),
            'constraints' => array(
                new NotBlank(
                    array('message' => 'mautic.email.variant.weight.notblank')
                )
            )
        ));

        $builderComponents = $this->factory->getModel('email')->getBuilderComponents();

        if (!empty($builderComponents['abTestWinnerCriteria'])) {
            $criteria = $builderComponents['abTestWinnerCriteria']['criteria'];
            $choices  = $builderComponents['abTestWinnerCriteria']['choices'];

            $builder->add('winnerCriteria', 'choice', array(
                'label'      => 'mautic.email.form.abtestwinner',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.getEmailAbTestWinnerForm(this);'
                ),
                'expanded'   => false,
                'multiple'   => false,
                'choices'    => $choices,
                'empty_value' => 'mautic.core.form.chooseone',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'mautic.email.variant.winnercriteria.notblank')
                    )
                )
            ));

            foreach ($criteria as $k => $c) {
                if (isset($c['formType'])) {
                    $builder->add($k, $c['formType'], array(
                        'required' => false,
                        'label'    => false
                    ));
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "emailvariant";
    }
}