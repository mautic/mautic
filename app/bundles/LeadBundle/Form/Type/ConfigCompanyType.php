<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'company_unique_identifiers_operator',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.core.config.contact_unique_identifiers_operator.or'    => CompositeExpression::TYPE_OR,
                    'mautic.core.config.contact_unique_identifiers_operator.and'   => CompositeExpression::TYPE_AND,
                ],
                'label'             => 'mautic.core.config.unique_identifiers_operator',
                'required'          => false,
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.unique_identifiers_operator.tooltip',
                ],
                'placeholder'       => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'companyconfig';
    }
}
