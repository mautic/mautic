<?php

namespace Mautic\CoreBundle\Tests\Unit\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AbstractFormControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AbstractFormController
     */
    private $classFromAbstractFormController;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ParameterBag
     */
    private $parameterBagMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Form
     */
    private $formMock;

    /**
     * Create a new instance from the AbstractFormController Class and creates mocks.
     */
    protected function setUp(): void
    {
        $security                              = $this->createMock(CorePermissions::class);
        $userHelper                            = $this->createMock(UserHelper::class);
        $this->classFromAbstractFormController = new class($security, $userHelper) extends AbstractFormController {
            public function returnIsFormCancelled(Form $form): bool
            {
                return $this->isFormCancelled($form);
            }
        };
        $this->parameterBagMock     = $this->createMock(ParameterBag::class);
        $this->formMock             = $this->createMock(Form::class);

        $requestMock          = $this->createMock(Request::class);
        $requestMock->request = $this->parameterBagMock;
        $requestStack         = new RequestStack();
        $requestStack->push($requestMock);

        $this->classFromAbstractFormController->setRequestStack($requestStack);
    }

    /**
     * Test to send a Form that does not have an array representation in request.
     */
    public function testIsFormCancelledWhenFormArrayNull(): void
    {
        $this->parameterBagMock->method('get')
            ->with('company')
            ->willReturn(null);
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
        $this->parameterBagMock->method('get')
            ->with('company_merge')
            ->willReturn(['buttons' => ['cancel' => null]]);
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
        $this->parameterBagMock->method('get')
            ->with('company_merge')
            ->willReturn(['buttons' => ['submit' => null]]);
        $this->formMock->method('getName')
            ->willReturn('company_merge');
        $isFormCancelled = $this->classFromAbstractFormController->returnIsFormCancelled($this->formMock);
        $this->assertFalse($isFormCancelled);
    }
}
