<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\ConfigBundle\Form\DataTransformer\DsnTransformerFactory;
use Mautic\ConfigBundle\Form\Type\DsnType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Form\Type\ConfigMonitoredEmailType;
use Mautic\EmailBundle\Form\Type\ConfigMonitoredMailboxesType;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Validator\DsnValidator;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenListValidator;
use Mautic\LeadBundle\Validator\CustomFieldValidator;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Form\Type\PreferenceCenterListType;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        // Some local environments do not have ext-imap loaded, but Mailbox uses these
        // constants in method signatures and class loading fails without them.
        defined('SORTARRIVAL') or define('SORTARRIVAL', 0);
        defined('SE_UID') or define('SE_UID', 1);
        defined('FT_PEEK') or define('FT_PEEK', 2);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $repoMock = $this->createMock(PageRepository::class);
        $repoMock->method('getPageList')->willReturn([]);

        $pageModelMock = $this->createMock(PageModel::class);
        $pageModelMock->method('getRepository')->willReturn($repoMock);

        $permsMock = $this->createMock(CorePermissions::class);
        $permsMock->method('isGranted')->willReturn(false);

        $dsnType              = new DsnType(
            $this->createStub(DsnTransformerFactory::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $configType                     = new ConfigType($translator);
        $preferenceCenterList           = new PreferenceCenterListType($pageModelMock, $permsMock);
        $configMonitoredEmail           = new ConfigMonitoredEmailType(new EventDispatcher());
        $configMonitoredMailboxes       = new ConfigMonitoredMailboxesType($this->createStub(Mailbox::class));
        $dsnValidator                   = new DsnValidator($this->createStub(TransportFactory::class));
        $emailValidator                 = $this->createMock(EmailValidator::class);
        $customFieldValidator           = $this->createMock(CustomFieldValidator::class);
        $emailOrEmailTokenListValidator = new EmailOrEmailTokenListValidator($emailValidator, $customFieldValidator);
        $validator                      = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                DsnValidator::class                   => $dsnValidator,
                EmailOrEmailTokenListValidator::class => $emailOrEmailTokenListValidator,
            ]))
            ->getValidator();

        return [
            new ValidatorExtension($validator),
            new PreloadedExtension([$configType, $dsnType, $preferenceCenterList, $configMonitoredEmail, $configMonitoredMailboxes], []),
        ];
    }

    public function testNewConfigFieldsArePresentInForm(): void
    {
        $form = $this->factory->create(ConfigType::class, []);

        $this->assertTrue($form->has('email_default_preference_center_id'), 'email_default_preference_center_id field is missing');
        $this->assertTrue($form->has('email_default_utm_source'), 'email_default_utm_source field is missing');
        $this->assertTrue($form->has('email_default_utm_medium'), 'email_default_utm_medium field is missing');
        $this->assertTrue($form->has('email_default_utm_campaign'), 'email_default_utm_campaign field is missing');
        $this->assertTrue($form->has('email_default_utm_content'), 'email_default_utm_content field is missing');
    }

    public function testUtmFieldsAreTextTypeAndNotRequired(): void
    {
        $form = $this->factory->create(ConfigType::class, []);

        foreach (['email_default_utm_source', 'email_default_utm_medium', 'email_default_utm_campaign', 'email_default_utm_content'] as $field) {
            $child = $form->get($field);
            $this->assertInstanceOf(TextType::class, $child->getConfig()->getType()->getInnerType(), "{$field} should be TextType");
            $this->assertFalse($child->getConfig()->getOption('required'), "{$field} should not be required");
        }
    }

    public function testPreferenceCenterFieldIsNotMultipleAndNotRequired(): void
    {
        $form  = $this->factory->create(ConfigType::class, []);
        $field = $form->get('email_default_preference_center_id');

        $this->assertFalse($field->getConfig()->getOption('multiple'), 'preference center field should have multiple=false');
        $this->assertFalse($field->getConfig()->getOption('required'), 'preference center field should not be required');
    }

    public function testFormSubmitsUtmValuesCorrectly(): void
    {
        $form = $this->factory->create(ConfigType::class, []);

        $form->submit([
            'email_default_utm_source'   => 'newsletter',
            'email_default_utm_medium'   => 'email',
            'email_default_utm_campaign' => 'spring-promo',
            'email_default_utm_content'  => 'header-link',
        ]);

        $this->assertTrue($form->isSynchronized());
        $data = $form->getData();

        $this->assertSame('newsletter', $data['email_default_utm_source']);
        $this->assertSame('email', $data['email_default_utm_medium']);
        $this->assertSame('spring-promo', $data['email_default_utm_campaign']);
        $this->assertSame('header-link', $data['email_default_utm_content']);
    }
}
