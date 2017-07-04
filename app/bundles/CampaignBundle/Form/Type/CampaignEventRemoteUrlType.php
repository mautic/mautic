<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SortableListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Class CampaignEventRemoteUrlType.
 */
class CampaignEventRemoteUrlType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'url',
            'text',
            [
                'label'       => 'mautic.campaign.event.remoteurl.url',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'constraints' => [
                    new Url(
                        [
                            'message' => 'mautic.form.submission.url.invalid',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'method',
            'choice',
            [
                'choices' => [
                    'get'     => 'GET',
                    'post'    => 'POST',
                    'options' => 'OPTIONS',
                    'put'     => 'PUT',
                    'patch'   => 'PATCH',
                    'trace'   => 'TRACE',
                ],
                'multiple'   => false,
                'label_attr' => ['class' => 'control-label'],
                'label'      => 'mautic.campaign.event.remoteurl.method',
                'attr'       => [
                    'class' => 'form-control',
                ],
                'empty_value' => false,
                'required'    => false,
            ]
        );

        $builder->add(
            'authorization_header',
            'text',
            [
                'label'      => 'mautic.form.action.repost.authorization_header',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.action.repost.authorization_header.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'additional_data',
            SortableListType::class,
            [
                'required'        => false,
                'label'           => 'mautic.campaign.event.remoteurl.data',
                'option_required' => false,
                'with_labels'     => true,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaignevent_remoteurl';
    }
}
