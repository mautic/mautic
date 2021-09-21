<?php

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\NotificationBundle\Form\Type\MobileNotificationDetailsType;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MobileNotificationDetailsTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * @var MobileNotificationDetailsType
     */
    protected $form;

    protected function setUp(): void
    {
        parent::setUp();

        $featureSettings = [
            'platforms' => [
                'ios',
            ],
        ];

        $integration = $this->createMock(Integration::class);
        $integration->method('getFeatureSettings')->willReturn($featureSettings);

        $abstractIntegration = $this->createMock(AbstractIntegration::class);
        $abstractIntegration->method('getIntegrationSettings')->willReturn($integration);

        $integrationHelper    = $this->createMock(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObject')->with('OneSignal')->willReturn($abstractIntegration);

        $this->form = new MobileNotificationDetailsType($integrationHelper);

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formBuilder->method('create')->willReturnSelf();
    }

    public function testBuildForm(): void
    {
        $options = [];

        $this->formBuilder->expects($this->exactly(8))
            ->method('add')
            ->withConsecutive(
                [
                    'additional_data',
                    SortableListType::class,
                    [
                        'required'        => false,
                        'label'           => 'mautic.notification.tab.data',
                        'option_required' => false,
                        'with_labels'     => true,
                    ],
                ],
                [
                    'ios_subtitle',
                    TextType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_subtitle',
                        'attr'  => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.notification.form.mobile.ios_subtitle.tooltip',
                        ],
                        'required'    => true,
                        'constraints' => new NotBlank(),
                    ],
                ],
                [
                    'ios_sound',
                    TextType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_sound',
                        'attr'  => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.notification.form.mobile.ios_sound.tooltip',
                        ],
                        'required' => false,
                    ],
                ],
                [
                    'ios_badges',
                    ButtonGroupType::class,
                    [
                        'choices' => [
                            'mautic.notification.form.mobile.ios_badges.set'       => 'SetTo',
                            'mautic.notification.form.mobile.ios_badges.increment' => 'Increase',
                        ],
                        'attr'              => [
                            'tooltip' => 'mautic.notification.form.mobile.ios_badges.tooltip',
                        ],
                        'label'       => 'mautic.notification.form.mobile.ios_badges',
                        'empty_data'  => 'None',
                        'required'    => false,
                        'placeholder' => 'mautic.notification.form.mobile.ios_badges.placeholder',
                        'expanded'    => true,
                        'multiple'    => false,
                    ],
                ],
                [
                    'ios_badgeCount',
                    IntegerType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_badgecount',
                        'attr'  => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.notification.form.mobile.ios_badgecount.tooltip',
                            'data-show-on' => '{"mobile_notification_mobileSettings_ios_badges_placeholder":""}',
                        ],
                        'required' => false,
                    ],
                ],
                [
                    'ios_contentAvailable',
                    CheckboxType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_contentavailable',
                        'attr'  => [
                            'tooltip' => 'mautic.notification.form.mobile.ios_contentavailable.tooltip',
                        ],
                        'required' => false,
                    ],
                ],
                [
                    'ios_media',
                    FileType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_media',
                        'attr'  => [
                            'tooltip' => 'mautic.notification.form.mobile.ios_media.tooltip',
                        ],
                        'required' => false,
                    ],
                ],
                [
                    'ios_mutableContent',
                    CheckboxType::class,
                    [
                        'label' => 'mautic.notification.form.mobile.ios_mutablecontent',
                        'attr'  => [
                            'tooltip' => 'mautic.notification.form.mobile.mutablecontent.tooltip',
                        ],
                        'required' => false,
                    ],
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }
}
