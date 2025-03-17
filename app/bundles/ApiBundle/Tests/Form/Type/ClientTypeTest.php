<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Form\Type;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\ApiBundle\Form\Type\ClientType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientTypeTest extends TestCase
{
    private ClientType $clientType;

    /**
     * @var MockObject&RequestStack
     */
    private MockObject $requestStack;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&ValidatorInterface
     */
    private MockObject $validator;

    /**
     * @var MockObject&RouterInterface
     */
    private MockObject $router;

    /**
     * @var MockObject&FormBuilderInterface
     */
    private MockObject $builder;

    /**
     * @var MockObject&Request
     */
    private MockObject $request;

    private Client $client;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);
        $this->validator    = $this->createMock(ValidatorInterface::class);
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
