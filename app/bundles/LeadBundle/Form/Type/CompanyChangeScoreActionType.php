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

/**
 * Class CompanyChangeScoreActionType.
 */
class CompanyChangeScoreActionType extends AbstractType
{
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
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'scorecontactscompanies_action';
    }
}
