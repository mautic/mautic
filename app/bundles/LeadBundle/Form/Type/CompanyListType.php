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

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'               => 'mautic.lead.lead.companies',
                'entity_label_column' => 'companyname',
                'modal_route'         => 'mautic_company_action',
                'modal_header'        => 'mautic.company.new.company',
                'model'               => 'lead.company',
                'ajax_lookup_action'  => function (Options $options) {
                    $query = [
                        'limit' => $options['limit'],
                    ];

                    return 'lead:getLookupChoiceList&'.http_build_query($query);
                },
                'repo_lookup_method'  => 'getAjaxSimpleList',
                'lookup_arguments'    => function (Options $options) {
                    return [
                        'expr'       => null,
                        'parameters' => [],
                        'label'      => null,
                        'value'      => 'id',
                        'limit'      => 100,
                        'include'    => '$data',
                    ];
                },
                'multiple'            => true,
                'main_entity'         => null,
                'limit'               => 100,
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'company_list';
    }
}
