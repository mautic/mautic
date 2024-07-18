<?php

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Form\Type\ExampleSendType;
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

    public function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->security   = $this->createMock(CorePermissions::class);
        $this->form       = new ExampleSendType($this->translator, $this->security);

        parent::setUp();
    }

    public function testBuildFormWithoutContact(): void
    {
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
                        'save_icon'  => 'fa fa-send',
                    ],
                ]
            );

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);
        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother')
            ->willReturn(false);

        $this->form->buildForm($builder, []);
    }

    public function testBuildFormWithContact(): void
    {
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
                            'data-callback'          => 'activateContactLookupField',
                            'data-toggle'            => 'field-lookup',
                            'data-lookup-callback'   => 'updateContactLookupListFilter',
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
                        'save_icon'  => 'fa fa-send',
                    ],
                ]
            );

        $this->security->expects(self::once())
            ->method('isAdmin')
            ->willReturn(false);
        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with('lead:leads:viewown', 'lead:leads:viewother')
            ->willReturn(true);

        $this->form->buildForm($builder, []);
    }
}
