<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SmsListType.
 */
class SmsListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_sms_action',
                'modal_header'        => 'mautic.sms.header.new',
                'model'               => 'sms',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options) {
                    return [
                        'type'    => 'sms',
                        'filter'  => '$data',
                        'limit'   => 0,
                        'start'   => 0,
                        'options' => [
                            'sms_type' => $options['sms_type'],
                        ],
                    ];
                },
                'ajax_lookup_action' => function (Options $options) {
                    $query = [
                        'sms_type' => $options['sms_type'],
                    ];

                    return 'sms:getLookupChoiceList&'.http_build_query($query);
                },
                'multiple' => true,
                'required' => false,
                'sms_type' => 'template',
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sms_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
