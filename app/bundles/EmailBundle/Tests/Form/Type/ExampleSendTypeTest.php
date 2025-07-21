<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Form\Type\ExampleSendType;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExampleSendTypeTest extends TestCase
{
    private ExampleSendType $form;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var CorePermissions&MockObject
     */
    private MockObject $security;

    /**
     * @var UserHelper|MockObject
     */
    private $userHelperMock;

    public function setUp(): void
    {
        $this->translator     = $this->createMock(TranslatorInterface::class);
        $this->security       = $this->createMock(CorePermissions::class);
        $this->userHelperMock = $this->createMock(UserHelper::class);
        $this->form           = new ExampleSendType($this->translator, $this->security, $this->userHelperMock);

        parent::setUp();
    }

    public function testBuildFormWithoutContact(): void
    {
        $userId  = 37;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'emails',
                    SortableListType::class,
                    [
                        'entry_type'       => EmailType::class,
                        'label'            => 'mautic.email.example_recipients',
                        'add_value_button' => 'mautic.email.add_recipient',
                        'option_notblank'  => false,
                    ],
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'apply_text' => false,
                        'save_text'  => 'mautic.email.send',
                        'save_icon'  => 'ri-send-plane-line',
                    ],
                ]
            );

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);

        $userMock = $this->createMock(User::class);
        $userMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->userHelperMock->expects(self::once())
            ->method('getUser')
            ->willReturn($userMock);

        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother', $userId)
            ->willReturn(false);

        $this->form->buildForm($builder, []);
    }

    public function testBuildFormWithContact(): void
    {
        $userId = 37;
        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.list.form.startTyping'],
                ['mautic.core.form.nomatches']
            )->willReturnOnConsecutiveCalls(
                'startTyping',
                'nomatches'
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(4))
            ->method('add')
            ->withConsecutive(
                [
                    'emails',
                    SortableListType::class,
                    [
                        'entry_type'       => EmailType::class,
                        'label'            => 'mautic.email.example_recipients',
                        'add_value_button' => 'mautic.email.add_recipient',
                        'option_notblank'  => false,
                    ],
                ],
                [
                    'contact',
                    LookupType::class,
                    [
                        'attr' => [
                            'class'                  => 'form-control',
                            'data-callback'          => 'activateExampleContactLookupField',
                            'data-toggle'            => 'field-lookup',
                            'data-lookup-callback'   => 'updateExampleContactLookupListFilter',
                            'data-chosen-lookup'     => 'lead:contactList',
                            'placeholder'            => 'startTyping',
                            'data-no-record-message' => 'nomatches',
                        ],
                    ],
                ],
                [
                    'contact_id',
                    HiddenType::class,
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'apply_text' => false,
                        'save_text'  => 'mautic.email.send',
                        'save_icon'  => 'ri-send-plane-line',
                    ],
                ]
            );

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);

        $userMock = $this->createMock(User::class);
        $userMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->userHelperMock->expects(self::once())
            ->method('getUser')
            ->willReturn($userMock);

        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother', $userId)
            ->willReturn(true);

        $this->form->buildForm($builder, []);
    }
}
