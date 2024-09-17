<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\EmailType;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject|EntityManager
     */
    private MockObject $entityManager;

    /**
     * @var MockObject|StageModel
     */
    private MockObject $stageModel;

    /**
     * @var MockObject|FormBuilderInterface
     */
    private MockObject $formBuilder;

    private EmailType $form;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParametersHelper;

    private MockObject $emailConfig;

    /**
     * @var ThemeHelperInterface|MockObject
     */
    private MockObject $themeHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->stageModel           = $this->createMock(StageModel::class);
        $this->formBuilder          = $this->createMock(FormBuilderInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->themeHelper          = $this->createMock(ThemeHelperInterface::class);
        $this->emailConfig          = $this->createMock(EmailConfigInterface::class);
        $this->form                 = new EmailType(
            $this->translator,
            $this->entityManager,
            $this->stageModel,
            $this->coreParametersHelper,
            $this->themeHelper,
            $this->emailConfig
        );

        $this->formBuilder->method('create')->willReturnSelf();
    }

    public function testBuildForm(): void
    {
        $options = ['data' => new Email()];
        $names   = [];
        $this->themeHelper
            ->expects($this->once())
            ->method('getCurrentTheme')
            ->with('blank', 'email')
            ->willReturn('blank');

        $this->formBuilder->method('add')
            ->with(
                $this->callback(
                    function ($name) use (&$names) {
                        $names[] = $name;

                        return true;
                    }
                )
            );

        $this->form->buildForm($this->formBuilder, $options);

        Assert::assertContains('buttons', $names);
    }
}
