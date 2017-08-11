<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\PageBundle\Helper\TrackingHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class TrackingPixelSendType.
 */
class TrackingPixelSendType extends AbstractType
{
    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * TrackingPixelSendType constructor.
     *
     * @param TrackingHelper $trackingHelper
     */
    public function __construct(TrackingHelper $trackingHelper)
    {
        $this->trackingHelper = $trackingHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $trackingServices = $this->trackingHelper->getEnabledServices();

        $builder->add('services', 'choice', [
            'label'      => 'mautic.page.tracking.form.services',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'expanded'    => false,
            'multiple'    => true,
            'choices'     => $trackingServices,
            'empty_value' => 'mautic.core.form.chooseone',
            'constraints' => [
                new NotBlank(
                    ['message' => 'mautic.core.ab_test.winner_criteria.not_blank']
                ),
            ],
        ]);

        $builder->add(
            'category',
            'text',
            [
                'label'      => 'mautic.page.tracking.form.category',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.tracking.form.category.descr',

                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'action',
            'text',
            [
                'label'      => 'mautic.page.tracking.form.action',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'label',
            'text',
            [
                'label'      => 'mautic.page.tracking.form.label',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'tracking_pixel_send_action';
    }
}
