<?php

namespace Mautic\CoreBundle\Tests\Unit\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AbstractFormControllerTest extends \PHPUnit\Framework\TestCase
{
    private \Mautic\CoreBundle\Controller\AbstractFormController $classFromAbstractFormController;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Form
     */
    private \PHPUnit\Framework\MockObject\MockObject $formMock;

    private RequestStack $requestStack;

    /**
     * Create a new instance from the AbstractFormController Class and creates mocks.
     */
    protected function setUp(): void
    {
        $doctrine             = $this->createMock(ManagerRegistry::class);
        $factory              = $this->createMock(MauticFactory::class);
        $modelFactory         = $this->createMock(ModelFactory::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $translator           = $this->createMock(Translator::class);
        $flashBag             = $this->createMock(FlashBag::class);
        $this->requestStack   = new RequestStack();
        $security             = $this->createMock(CorePermissions::class);

        $this->classFromAbstractFormController = new class($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $this->requestStack, $security) extends AbstractFormController {
            public function returnIsFormCancelled(Form $form): bool
            {
                return $this->isFormCancelled($form);
            }
        };
        $this->formMock = $this->createMock(Form::class);
    }

    /**
     * Test to send a Form that does not have an array representation in request.
     */
    public function testIsFormCancelledWhenFormArrayNull(): void
    {
        $this->prepareRequestStack(['company' => null]);

        $this->formMock->method('getName')
            ->willReturn('company');
        $isFormCancelled = $this->classFromAbstractFormController->returnIsFormCancelled($this->formMock);
        $this->assertFalse($isFormCancelled);
    }

    /**
     * Test to send a Form that has an array representation in request. And the cancel button was clicked.
     */
    public function testIsFormCancelledWhenCancelled(): void
    {
        $this->prepareRequestStack(['company_merge' => ['buttons' => ['cancel' => null]]]);

        $this->formMock->method('getName')
            ->willReturn('company_merge');
        $isFormCancelled = $this->classFromAbstractFormController->returnIsFormCancelled($this->formMock);
        $this->assertTrue($isFormCancelled);
    }

    /**
     * Test to send a Form that has an array representation in request. And the submit button was clicked.
     */
    public function testIsFormCancelledWhenNotCancelled(): void
    {
        $this->prepareRequestStack(['company_merge' => ['buttons' => ['submit' => null]]]);

        $this->formMock->method('getName')
            ->willReturn('company_merge');
        $isFormCancelled = $this->classFromAbstractFormController->returnIsFormCancelled($this->formMock);
        $this->assertFalse($isFormCancelled);
    }

    private function prepareRequestStack(mixed $inputBagParameters): void
    {
        $requestMock          = $this->createMock(Request::class);
        $requestMock->request = new InputBag($inputBagParameters);
        $this->requestStack->push($requestMock);
    }
}
