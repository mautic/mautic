<?php

namespace Mautic\ConfigBundle\Tests\Form\Helper;

use Mautic\ConfigBundle\Form\DataTransformer\DsnTransformerFactory;
use Mautic\ConfigBundle\Form\Helper\RestrictionHelper;
use Mautic\ConfigBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\Form\Type\DsnType;
use Mautic\ConfigBundle\Form\Type\EscapeTransformer;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\EventListener\ProcessBounceSubscriber;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\Form\Type\ConfigMonitoredEmailType;
use Mautic\EmailBundle\Form\Type\ConfigMonitoredMailboxesType;
use Mautic\EmailBundle\Form\Type\ConfigType as EmailConfigType;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Mocking a representative ConfigForm by leveraging Symfony's TypeTestCase to test RestrictionHelper.
 */
class RestrictionHelperTest extends TypeTestCase
{
    /**
     * @var string
     */
    private $displayMode = RestrictionHelper::MODE_REMOVE;

    /**
     * @var array
     */
    private $restrictedFields = [
        'monitored_email' => [
            'EmailBundle_bounces',
            'EmailBundle_unsubscribes' => [
                'address',
            ],
        ],
    ];

    private $forms = [
        'emailconfig' => [
            'bundle'     => 'EmailBundle',
            'formAlias'  => 'emailconfig',
            'formType'   => EmailConfigType::class,
            'formTheme'  => 'MauticEmailBundle:FormTheme\\Config',
            'parameters' => [
                'mailer_from_name'                      => 'Mautic',
                'mailer_from_email'                     => 'email@yoursite.com',
                'mailer_return_path'                    => null,
                'mailer_transport'                      => 'mail',
                'mailer_append_tracking_pixel'          => true,
                'mailer_convert_embed_images'           => false,
                'mailer_dsn'                            => 'smtp://null:25',
                'messenger_dsn_email'                   => 'doctrine://default',
                'messenger_retry_strategy_max_retries'  => 3,
                'messenger_retry_strategy_delay'        => 1000,
                'messenger_retry_strategy_multiplier'   => 2,
                'messenger_retry_strategy_max_delay'    => 0,
                'unsubscribe_text'                      => null,
                'webview_text'                          => null,
                'unsubscribe_message'                   => null,
                'resubscribe_message'                   => null,
                'monitored_email'                       => [
                    'general' => [
                        'address'    => null,
                        'host'       => null,
                        'port'       => '993',
                        'encryption' => '/ssl',
                        'user'       => null,
                        'password'   => null,
                    ],
                    'EmailBundle_bounces' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                    'EmailBundle_unsubscribes' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                    'EmailBundle_replies' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                ],
                'mailer_is_owner'                     => false,
                'default_signature_text'              => null,
                'email_frequency_number'              => null,
                'email_frequency_time'                => null,
                'show_contact_preferences'            => false,
                'show_contact_frequency'              => false,
                'show_contact_pause_dates'            => false,
                'show_contact_preferred_channels'     => false,
                'show_contact_categories'             => false,
                'show_contact_segments'               => false,
                'mailer_mailjet_sandbox'              => false,
                'mailer_mailjet_sandbox_default_mail' => null,
                'disable_trackable_urls'              => false,
            ],
        ],
    ];

    /**
     * @testdox Test that the restricted fields are removed from the config
     *
     * @covers \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::applyRestrictions
     * @covers \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::restrictField
     */
    public function testRestrictedFieldsAreRemoved(): void
    {
        $form = $this->factory->create(ConfigType::class, $this->forms);

        $this->assertTrue($form->has('emailconfig'));

        $emailConfig = $form->get('emailconfig');

        // monitored_email is partially restricted so should be included
        $this->assertTrue($emailConfig->has('monitored_email'));

        $monitoredEmail = $emailConfig->get('monitored_email');

        // EmailBundle_bounces is restricted in entirety and thus should not be included
        $this->assertFalse($monitoredEmail->has('EmailBundle_bounces'));

        // EmailBundle_unsubscribes is partially restricted so should be included
        $this->assertTrue($monitoredEmail->has('EmailBundle_unsubscribes'));

        $unsubscribes = $monitoredEmail->get('EmailBundle_unsubscribes');

        // address under EmailBundle_unsubscribes is restricted so should not be included
        $this->assertFalse($unsubscribes->has('address'));

        // host under EmailBundle_unsubscribes is not restricted so should be included
        $this->assertTrue($unsubscribes->has('host'));
    }

    /**
     * @testdox Test that the restricted fields are masked
     *
     * @covers \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::applyRestrictions
     * @covers \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::restrictField
     */
    public function testRestrictedFieldsAreMasked(): void
    {
        $this->displayMode = RestrictionHelper::MODE_MASK;

        // Rebuild factory to get updated RestrictionHelper
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();

        $form = $this->factory->create(ConfigType::class, $this->forms);
        /** @var FormInterface<mixed> $address */
        $address = $form['emailconfig']['monitored_email']['EmailBundle_unsubscribes']['address'];

        $this->assertTrue($address->getConfig()->getOption('attr')['readonly']);
        $this->assertTrue($address->getConfig()->getOption('disabled'));
        $this->assertEquals(
            [
                'class'        => 'form-control',
                'tooltip'      => 'mautic.email.config.monitored_email_address.tooltip',
                'data-show-on' => '{"config_emailconfig_monitored_email_EmailBundle_unsubscribes_override_settings_1": "checked"}',
                'placeholder'  => 'mautic.config.restricted',
                'readonly'     => true,
            ],
            $address->getConfig()->getOption('attr')
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturnCallback(
                fn ($key) => $key
            );

        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validator
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));
        $validator
            ->method('getMetadataFor')
            ->will($this->returnValue(new ClassMetadata(Form::class)));

        $imapHelper = $this->getMockBuilder(Mailbox::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Register monitored email listeners
        $dispatcher = new EventDispatcher();
        $bouncer    = $this->getMockBuilder(Bounce::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dispatcher->addSubscriber(new ProcessBounceSubscriber($bouncer));

        $unsubscriber = $this->getMockBuilder(Unsubscribe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $looper = $this->getMockBuilder(FeedbackLoop::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dispatcher->addSubscriber(new ProcessUnsubscribeSubscriber($unsubscriber, $looper));

        // This is what we're really testing here
        $restrictionHelper = new RestrictionHelper($translator, $this->restrictedFields, $this->displayMode);
        $escapeTransformer = new EscapeTransformer([]);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension(
                [
                    new TextType(),
                    new ChoiceType(),
                    new YesNoButtonGroupType(),
                    new PasswordType(),
                    new StandAloneButtonType(),
                    new NumberType(),
                    new FormButtonsType(),
                    new ButtonGroupType(),
                    new EmailConfigType($translator),
                    new DsnType($this->createMock(DsnTransformerFactory::class), $this->createMock(CoreParametersHelper::class)),
                    new ConfigMonitoredEmailType($dispatcher),
                    new ConfigMonitoredMailboxesType($imapHelper),
                    new ConfigType($restrictionHelper, $escapeTransformer),
                ],
                []
            ),
            new ValidatorExtension($validator),
        ];
    }
}
