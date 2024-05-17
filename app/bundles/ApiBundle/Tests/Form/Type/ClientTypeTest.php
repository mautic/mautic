<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Form\Type;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\ApiBundle\Form\Type\ClientType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientTypeTest extends TestCase
{
    private ClientType $clientType;

    /**
     * @var RequestStack
     */
    private \PHPUnit\Framework\MockObject\MockObject $requestStack;

    /**
     * @var TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    /**
     * @var ValidatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $validator;

    /**
     * @var Session
     */
    private \PHPUnit\Framework\MockObject\MockObject $session;

    /**
     * @var RouterInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $router;

    /**
     * @var FormBuilderInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $builder;

    /**
     * @var Request
     */
    private \PHPUnit\Framework\MockObject\MockObject $request;

    private Client $client;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);
        $this->validator    = $this->createMock(ValidatorInterface::class);
        $this->session      = $this->createMock(Session::class);
        $this->router       = $this->createMock(RouterInterface::class);
        $this->builder      = $this->createMock(FormBuilderInterface::class);
        $this->request      = $this->createMock(Request::class);
        $this->client       = new Client();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('api_mode', null);

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
        $options = [
            'data' => $this->client,
        ];

        $this->builder->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $cleanSubscriber    = new CleanFormSubscriber([]);
        $formExitSubscriber = new FormExitSubscriber('api.client', $options);

        $this->builder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$cleanSubscriber],
                [$formExitSubscriber]
            );

        $this->clientType->buildForm($this->builder, $options);
    }
}
