<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\Helper;

use Mautic\ApiBundle\Form\Type\ClientType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientTypeTest extends TestCase
{
    /**
     * @var ClientType
     */
    private $clientType;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->builder = $this->createMock(FormBuilderInterface::class);

        $this->clientType = new ClientType(
            $this->requestStack,
            $this->translator,
            $this->validator,
            $this->session,
            $this->router
        );
    }

    public function testThatBuildFormCallsEventSubscribers(): void
    {
        $this->clientType->buildForm($this->builder, []);
    }
}