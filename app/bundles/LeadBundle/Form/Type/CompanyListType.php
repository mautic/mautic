<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CompanyListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'label'               => 'mautic.lead.lead.companies',
                'entity_label_column' => 'companyname',
                'modal_route'         => 'mautic_company_action',
                'modal_header'        => 'mautic.company.new.company',
                'model'               => 'lead.company',
                'ajax_lookup_action'  => 'lead:getLookupChoiceList',
                'multiple'            => true,
                'main_entity'         => null,
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
