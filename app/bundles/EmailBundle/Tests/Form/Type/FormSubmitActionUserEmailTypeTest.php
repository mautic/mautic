<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\EmailBundle\Form\Type\FormSubmitActionUserEmailType;
use Mautic\StageBundle\Model\StageModel;
use Mautic\UserBundle\Form\Type\UserListType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormSubmitActionUserEmailTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|StageModel
     */
    private $stageModel;

    /**
     * @var MockObject|FormBuilderInterface
     */
    private MockObject $formBuilder;

    private FormSubmitActionUserEmailType $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder          = $this->createMock(FormBuilderInterface::class);
        $this->form                 = new FormSubmitActionUserEmailType();
        $this->formBuilder->method('create')->willReturnSelf();
    }

    public function testBuildForm(): void
    {
        $options = [];
        $matcher = $this->exactly(2);

        $this->formBuilder->expects($matcher)
            ->method('add')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('useremail', $parameters[0]);
                    $this->assertSame(EmailSendType::class, $parameters[1]);
                    $this->assertSame([
                        'label' => 'mautic.email.emails',
                        'attr'  => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.email.choose.emails_descr',
                        ],
                        'update_select' => 'formaction_properties_useremail_email',
                    ], $parameters[2]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('user_id', $parameters[0]);
                    $this->assertSame(UserListType::class, $parameters[1]);
                    $this->assertEquals([
                        'label'      => 'mautic.email.form.users',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.core.help.autocomplete',
                        ],
                        'required'    => true,
                        'constraints' => new NotBlank(
                            [
                                'message' => 'mautic.core.value.required',
                            ]
                        ),
                    ], $parameters[2]);
                }

                return $this->formBuilder;
            });

        $this->form->buildForm($this->formBuilder, $options);
    }
}
