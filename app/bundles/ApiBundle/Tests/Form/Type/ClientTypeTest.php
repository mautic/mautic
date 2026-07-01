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

final class ClientTypeTest extends TestCase
{
    private ClientType $clientType;

    /**
     * @var MockObject&FormBuilderInterface
     */
    private MockObject $builder;

    private Client $client;

    protected function setUp(): void
    {
        $requestStack       = $this->createMock(RequestStack::class);
        $translator         = $this->createMock(TranslatorInterface::class);
        $validator          = $this->createMock(ValidatorInterface::class);
        $router             = $this->createMock(RouterInterface::class);
        $this->builder      = $this->createMock(FormBuilderInterface::class);
        $request            = $this->createMock(Request::class);
        $this->client       = new Client();

        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $request->expects($this->once())
            ->method('get')
            ->with('api_mode', null);

        $this->clientType = new ClientType(
            $requestStack,
            $translator,
            $validator,
            $router
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
        $matcher            = $this->exactly(2);

        $this->builder->expects($matcher)
            ->method('addEventSubscriber')->willReturnCallback(function (...$parameters) use ($matcher, $cleanSubscriber, $formExitSubscriber): MockObject {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($cleanSubscriber, $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertEquals($formExitSubscriber, $parameters[0]);
                }

                return $this->builder;
            });

        $this->clientType->buildForm($this->builder, $options);
    }
}
