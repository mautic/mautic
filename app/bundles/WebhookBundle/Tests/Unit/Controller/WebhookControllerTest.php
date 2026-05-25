<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use JMS\Serializer\SerializerInterface;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\EventListener\WebhookSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\WebhookBundle\Controller\AjaxController;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\EventRepository;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\WebhookBundle\Service\WebhookService;
use Mautic\WebhookBundle\WebhookEvents;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebhookControllerTest extends TestCase
{
    #[DataProvider('provideNewOrUpdate')]
    public function testPayloadsAreSame(bool $isNew): void
    {
        $eventUnderTest = $isNew ? 'mautic.lead_post_save_new' : 'mautic.lead_post_save_update';
        $url            = 'https://mautic.com/';
        $secret         = 'secret! sssshhhh!';
        $webhookId      = 9274365435;
        $disableLimit   = 50;

        $mauticBundlesPath = realpath(__DIR__.'/../../../../');
        Assert::assertNotFalse($mauticBundlesPath);

        if ($isNew) {
            $leadPayloadJson = file_get_contents($mauticBundlesPath.'/LeadBundle/Assets/WebhookPayload/lead_post_save_new.json');
        } else {
            $leadPayloadJson = file_get_contents($mauticBundlesPath.'/LeadBundle/Assets/WebhookPayload/lead_post_save_update.json');
        }

        Assert::assertNotFalse($leadPayloadJson);
        $leadPayload = json_decode($leadPayloadJson, true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($leadPayload);

        Assert::assertArrayHasKey(0, $leadPayload);
        Assert::assertArrayHasKey('contact', $leadPayload[0]);
        $contactPayload = $leadPayload[0]['contact'];

        // Test payload contains old timestamp from the JSON file, while "real" payload contains a "now" timestamp.
        $testPayload = [
            $eventUnderTest => $leadPayload,
        ];

        Assert::assertArrayHasKey('timestamp', $leadPayload[0]);
        $leadPayload[0]['timestamp'] = (new \DateTime())->format(\DateTimeInterface::ATOM);
        $realTestPayload             = [
            $eventUnderTest => $leadPayload,
        ];

        $controller = new AjaxController(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(ModelFactory::class),
            $this->createMock(UserHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            $this->createMock(RequestStack::class),
            $this->createMock(CorePermissions::class),
        );

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->with('kernel.environment')
            ->willReturn('test');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('parameter_bag')->willReturn(true);
        $container->method('get')->with('parameter_bag')->willReturn($parameterBag);

        $controller->setContainer($container);

        $request = new Request();

        $request->request->set('url', $url);
        $request->request->set('types', [$eventUnderTest]);
        $request->request->set('secret', $secret);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('post')
            ->willReturnCallback(function (string $url, array $payload) use ($testPayload): GuzzleResponse {
                Assert::assertSame(
                    $payload,
                    $testPayload,
                    json_encode($payload, JSON_THROW_ON_ERROR),
                );

                return new GuzzleResponse();
            });

        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->method('getSystemPath')->willReturn(realpath(dirname(__DIR__, 4)));

        // Send test action.
        $testResponse = $controller->sendHookTestAction($request, $client, $pathsHelper);
        // If you encounter errors here, please check \Mautic\WebhookBundle\Controller\AjaxController::processWebhookTest
        // or inside the Client mock.
        Assert::assertSame(Response::HTTP_OK, $testResponse->getStatusCode());

        $changes = ['dateIdentified' => $isNew];

        // Now send the lead update event.
        $lead = $this->createMock(Lead::class);
        $lead->method('isAnonymous')->willReturn(false);
        $lead->method('getChanges')
            ->with(true)
            ->willReturn($changes);

        $event = new LeadEvent($lead, $isNew);

        $guzzleBody = $this->createMock(StreamInterface::class);
        $guzzleBody->method('getContents')->willReturn('whatever');

        $clientResponse = $this->createMock(GuzzleResponse::class);
        $clientResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $clientResponse->method('getBody')->willReturn($guzzleBody);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('post')
            ->willReturnCallback(function (string $callUrl, array $callPayload, string $callSecret) use ($realTestPayload, $clientResponse, $secret, $url): GuzzleResponse {
                Assert::assertSame($url, $callUrl);
                Assert::assertSame($realTestPayload, $callPayload);

                Assert::assertSame($secret, $callSecret);

                return $clientResponse;
            });

        $webhook = $this->createMock(Webhook::class);
        $webhook->method('getId')
            ->willReturn($webhookId);
        $webhook->method('getMarkedUnhealthyAt')
            ->willReturn(null);
        $webhook->method('getWebhookUrl')
            ->willReturn($url);
        $webhook->method('getSecret')
            ->willReturn($secret);

        $webhookEvent = $this->createMock(Event::class);
        $webhookEvent->method('getWebhook')->willReturn($webhook);
        $webhookEvent->method('getEventType')->willReturn($eventUnderTest);

        $webhookEventRepository = $this->createMock(EventRepository::class);
        $webhookEventRepository->expects($this->once())
            ->method('getEntitiesByEventType')
            ->with($eventUnderTest)
            ->willReturn([$webhookEvent]);

        $webhookQueueRepository = $this->createMock(WebhookQueueRepository::class);

        $webhookRepository = $this->createMock(WebhookRepository::class);
        $webhookRepository->expects($this->once())
            ->method('saveEntity');

        // If you encounter error here - debug the \Mautic\WebhookBundle\Model\WebhookModel::processWebhook
        // in the catch part.
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository->expects($this->never())
            ->method('getSuccessVsErrorStatusCodeRatio');

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                [Event::class, $webhookEventRepository],
                [WebhookQueue::class, $webhookQueueRepository],
                [Webhook::class, $webhookRepository],
            ]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')
            ->willReturnCallback(function (array $data, string $type) use ($contactPayload): string {
                Assert::assertArrayHasKey('contact', $data);
                Assert::assertSame('json', $type);

                return json_encode(['contact' => $contactPayload], JSON_THROW_ON_ERROR);
            });

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->exactly(3))
            ->method('hasListeners')
            ->willReturnMap([
                [WebhookEvents::WEBHOOK_QUEUE_ON_ADD, false],
                ['mautic.webhook_pre_save', false],
                ['mautic.webhook_post_save', false],
            ])
            ->willReturn(false);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnMap([
                ['webhook_limit', 10, 5],
                ['webhook_time_limit', 600, 500],
                ['webhook_disable_limit', 100, $disableLimit],
                ['webhook_timeout', 15, 25],
                ['webhook_log_max', WebhookModel::WEBHOOK_LOG_MAX, 50],
                ['queue_mode', null, WebhookModel::IMMEDIATE_PROCESS],
                ['events_orderby_dir', Order::Ascending, Order::Ascending],
                ['disable_auto_unpublish', null, true],
                ['webhook_retry_delay', 3600, 5000],
                ['clean_webhook_logs_in_background', null, true],
            ]);

        $webhookModel = new WebhookModel(
            $coreParametersHelper,
            $serializer,
            $client,
            $em,
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(WebhookService::class)
        );
        $leadModel = $this->createMock(LeadModel::class);

        $subscriber = new WebhookSubscriber($webhookModel, $leadModel);
        $subscriber->onLeadNewUpdate($event);
    }

    public static function provideNewOrUpdate(): \Generator
    {
        yield 'New' => [true];
        yield 'Update' => [false];
    }
}
