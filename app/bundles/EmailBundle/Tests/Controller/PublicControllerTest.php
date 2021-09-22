<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Templating\Helper\ThemeHelper as TemplatingThemeHelper;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Controller\PublicController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class PublicControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorMock;

    /**
     * @var MockObject|Session
     */
    private $sessionMock;

    /**
     * @var MockObject|ModelFactory
     */
    private $modelFactoryMock;

    /**
     * @var MockObject|Container
     */
    private $containerMock;

    /**
     * @var MockObject|Router
     */
    private $routerMock;

    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;

    /**
     * @var MockObject|Email
     */
    private $emailMock;

    /**
     * @var MockObject|FlashBag
     */
    private $flashBagMock;

    /**
     * @var EmailController
     */
    private $controller;

    /**
     * @var MockObject|CorePermissions
     */
    private $corePermissionsMock;

    /**
     * @var MockObject|UserHelper
     */
    private $helperUserMock;

    /**
     * @var MockObject|FormFactory
     */
    private $formFactoryMock;

    /**
     * @var MockObject|Form
     */
    private $formMock;

    /**
     * @var MockObject|DelegatingEngine
     */
    private $templatingMock;

    /**
     * @var CoreParametersHelper|mixed|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var MauticFactory|mixed|MockObject
     */
    private $factory;

    /**
     * @var TemplatingThemeHelper|mixed|MockObject
     */
    private $templatingThemeHelper;

    /**
     * @var ThemeHelper|mixed|MockObject
     */
    private $themeHelper;

    /**
     * @var LeadModel|mixed|MockObject
     */
    private $leadModel;

    /**
     * @var FormModel|mixed|MockObject
     */
    private $formModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock        = $this->createMock(TranslatorInterface::class);
        $this->sessionMock           = $this->createMock(Session::class);
        $this->modelFactoryMock      = $this->createMock(ModelFactory::class);
        $this->containerMock         = $this->createMock(Container::class);
        $this->routerMock            = $this->createMock(Router::class);
        $this->formModel             = $this->createMock(FormModel::class);
        $this->leadModel             = $this->createMock(LeadModel::class);
        $this->emailModel            = $this->createMock(EmailModel::class);
        $this->emailMock             = $this->createMock(Email::class);
        $this->flashBagMock          = $this->createMock(FlashBag::class);
        $this->corePermissionsMock   = $this->createMock(CorePermissions::class);
        $this->helperUserMock        = $this->createMock(UserHelper::class);
        $this->formFactoryMock       = $this->createMock(FormFactory::class);
        $this->formMock              = $this->createMock(Form::class);
        $this->templatingMock        = $this->createMock(DelegatingEngine::class);
        $this->coreParametersHelper  = $this->createMock(CoreParametersHelper::class);
        $this->factory               = $this->createMock(MauticFactory::class);
        $this->templatingThemeHelper = $this->createMock(TemplatingThemeHelper::class);
        $this->themeHelper           = $this->createMock(ThemeHelper::class);
        $this->controller            = new PublicController();
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFlashBag($this->flashBagMock);
        $this->controller->setCoreParametersHelper($this->coreParametersHelper);
        $this->controller->setFactory($this->factory);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
        $this->controller->setRequest(new Request());
    }

    public function testUnsubscribeAction(): void
    {
        $stat  = new Stat();
        $email = new Email();
        $email->setTemplate('mautic_code_mode');
        $form = new \Mautic\FormBundle\Entity\Form();
        $email->setUnsubscribeForm($form);
        $stat->setEmail($email);

        $this->emailModel->method('getEmailStatus')->willReturn($stat);

        $this->routerMock
            ->method('generate')
            ->willReturn('https://url');

        $this->modelFactoryMock
            ->method('getModel')
            ->withConsecutive(['email'], ['lead'], ['form'])
            ->willReturnOnConsecutiveCalls($this->emailModel, $this->leadModel, $this->formModel);

        $this->containerMock
            ->method('get')
            ->withConsecutive(['mautic.model.factory'], ['translator'], ['session'], ['mautic.model.factory'], ['mautic.model.factory'], ['mautic.helper.core_parameters'], ['router'])
            ->willReturnOnConsecutiveCalls($this->modelFactoryMock, $this->translatorMock, $this->sessionMock, $this->modelFactoryMock, $this->modelFactoryMock, $this->coreParametersHelper, $this->routerMock);

        $this->templatingThemeHelper->method('getConfig')->willReturn(['features'=> ['email']]);
        $this->themeHelper->method('checkForTwigTemplate')->willReturn('logical.name.html.php');

        $this->factory->method('getTheme')->willReturn($this->templatingThemeHelper);
        $this->factory->method('getHelper')->willReturn($this->themeHelper);

        $this->expectException(\LogicException::class);

        $this->controller->unsubscribeAction('xxx');
    }
}
