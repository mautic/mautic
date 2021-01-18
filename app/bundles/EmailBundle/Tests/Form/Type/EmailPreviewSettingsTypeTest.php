<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Form\Type\EmailPreviewSettingsType;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailPreviewSettingsTypeTest extends TestCase
{
    /**
     * @var EmailRepository|MockObject
     */
    private $emailRepository;

    /**
     * @var LeadRepository|MockObject
     */
    private $contactRepository;

    /**
     * @var EmailPreviewSettingsType|MockObject
     */
    private $form;

    protected function setUp()
    {
        $this->emailRepository   = $this->createMock(EmailRepository::class);
        $this->contactRepository = $this->createMock(LeadRepository::class);
        $this->form              = new EmailPreviewSettingsType($this->emailRepository, $this->contactRepository);

        parent::setUp();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['email' => null]);

        $this->form->configureOptions($resolver);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('email_preview_settings', $this->form->getBlockPrefix());
    }

    public function testBuildForm(): void
    {
        $emailId = 1;

        $email   = new Email();
        $email->setId($emailId);

        $this->emailRepository->expects(self::once())
            ->method('fetchPublishedEmailsWithVariantById')
            ->with($emailId)
            ->willReturn(null);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(3))
            ->method('add')
            ->withConsecutive([
                    'translation',
                    ChoiceType::class,
                    [
                        'choices' => [],
                    ],
                ],
                [
                    'variant',
                    ChoiceType::class,
                    [
                        'choices' => [],
                    ],
                ],
                [
                    'contact',
                    LookupType::class,
                    [],
                ]
            );

        $this->form->buildForm($builder, ['email' => $email]);
    }
}
