<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
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

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class AbstractFormControllerTest extends \PHPUnit\Framework\TestCase
{
    private AbstractFormController $classFromAbstractFormController;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Form
     */
    private \PHPUnit\Framework\MockObject\MockObject $formMock;

    private RequestStack $requestStack;

    /**
     * Create a new instance from the AbstractFormController Class and creates mocks.
     */
    protected function setUp(): void
    {
        $this->requestStack   = new RequestStack();

        $this->classFromAbstractFormController = new class($this->createStub(ManagerRegistry::class), $this->createStub(ModelFactory::class), $this->createStub(UserHelper::class), $this->createStub(CoreParametersHelper::class), $this->createStub(EventDispatcherInterface::class), $this->createStub(Translator::class), $this->createStub(FlashBag::class), $this->requestStack, $this->createStub(CorePermissions::class)) extends AbstractFormController {
        };
        $this->formMock = $this->createMock(Form::class);
    }

    /**
     * Test to send a Form that does not have an array representation in request.
     */
    public function testIsFormCancelledWhenFormArrayNull(): void
    {
        $this->prepareRequestStack(['company' => null]);

        $this->formMock->expects($this->once())->method('getName')
            ->willReturn('company');
        $isFormCancelled = $this->invokeIsFormCancelled($this->formMock);
        $this->assertFalse($isFormCancelled);
    }

    /**
     * Test to send a Form that has an array representation in request. And the cancel button was clicked.
     */
    public function testIsFormCancelledWhenCancelled(): void
    {
        $this->prepareRequestStack(['company_merge' => ['buttons' => ['cancel' => null]]]);

        $this->formMock->expects($this->once())->method('getName')
            ->willReturn('company_merge');
        $isFormCancelled = $this->invokeIsFormCancelled($this->formMock);
        $this->assertTrue($isFormCancelled);
    }

    /**
     * Test to send a Form that has an array representation in request. And the submit button was clicked.
     */
    public function testIsFormCancelledWhenNotCancelled(): void
    {
        $this->prepareRequestStack(['company_merge' => ['buttons' => ['submit' => null]]]);

        $this->formMock->expects($this->once())->method('getName')
            ->willReturn('company_merge');
        $isFormCancelled = $this->invokeIsFormCancelled($this->formMock);
        $this->assertFalse($isFormCancelled);
    }

    private function invokeIsFormCancelled(Form $form): bool
    {
        $reflection = new \ReflectionClass(AbstractFormController::class);
        $method     = $reflection->getMethod('isFormCancelled');

        return (bool) $method->invoke($this->classFromAbstractFormController, $form);
    }

    private function prepareRequestStack(mixed $inputBagParameters): void
    {
        $requestMock          = $this->createMock(Request::class);
        $requestMock->request = new InputBag($inputBagParameters);
        $this->requestStack->push($requestMock);
    }
}
