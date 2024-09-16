<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\EventListener\DashboardSubscriber;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class DashboardSubscriberTest extends TestCase
{
    /**
     * @var MockObject&AuditLogModel
     */
    private MockObject $auditLogModel;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&Router
     */
    private MockObject $router;

    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $security;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject&ModelFactory
     */
    private MockObject $modelFactory;

    /**
     * @var MockObject&WidgetDetailEvent
     */
    private MockObject $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogModel = $this->createMock(AuditLogModel::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->router        = $this->createMock(Router::class);
        $this->security      = $this->createMock(CorePermissions::class);
        $this->dispatcher    = $this->createMock(EventDispatcherInterface::class);
        $this->modelFactory  = $this->createMock(ModelFactory::class);
        $this->event         = $this->createMock(WidgetDetailEvent::class);
    }

    public function testSubscriberChecksForEventType(): void
    {
        $this->event->expects(self::once())
          ->method('getType')
          ->willReturn('random');
        $this->event->expects(self::never())
          ->method('isCached');
        $this->event->expects(self::never())
          ->method('setTemplate');

        $this->auditLogModel->expects(self::never())
          ->method('getLogForObject');

        $subscriber = new DashboardSubscriber(
          $this->auditLogModel,
          $this->translator,
          $this->router,
          $this->security,
          $this->dispatcher,
          $this->modelFactory
        );
        $subscriber->onWidgetDetailGenerate($this->event);
    }

    public function testSubscriberChecksForCache(): void
    {
        $this->event->expects(self::once())
          ->method('getType')
          ->willReturn('recent.activity');
        $this->event->expects(self::once())
          ->method('isCached')
          ->willReturn(true);
        $this->event->expects(self::once())
          ->method('setTemplate');
        $this->event->expects(self::once())
          ->method('stopPropagation');

        $this->auditLogModel->expects(self::never())
          ->method('getLogForObject');

        $subscriber = new DashboardSubscriber(
          $this->auditLogModel,
          $this->translator,
          $this->router,
          $this->security,
          $this->dispatcher,
          $this->modelFactory
        );
        $subscriber->onWidgetDetailGenerate($this->event);
    }

    public function testSubscriberGatherLogs(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->expects(self::once())
          ->method('getHeight')
          ->willReturn(1500);
        $this->event->expects(self::once())
          ->method('getType')
          ->willReturn('recent.activity');
        $this->event->expects(self::once())
          ->method('isCached')
          ->willReturn(false);
        $this->event->expects(self::once())
          ->method('getWidget')
          ->willReturn($widget);
        $this->event->expects(self::once())
          ->method('setTemplate');
        $this->event->expects(self::once())
          ->method('stopPropagation');

        $this->translator->expects(self::once())
          ->method('trans')
          ->with('mautic.lead.lead.anonymous')
          ->willReturn('whatever');

        $logs   = $expectedLogs   = [];
        $logs[] = $expectedLogs[] = ['something', 'else']; // corrupt database data
        $logs[] = $expectedLogs[] = ['bundle' => 'null', 'object' => 'object', 'objectId' => 123];
        $logs[] = $expectedLogs[] = ['bundle' => 'model', 'object' => 'not_form_model', 'objectId' => 234];
        $logs[] = $expectedLogs[] = ['bundle' => 'model', 'object' => 'has_no_getter', 'objectId' => 345];
        $logs[] = $expectedLogs[] = ['bundle' => 'item', 'object' => 'not_lead', 'objectId' => 456];
        $logs[] = $expectedLogs[] = ['bundle' => 'lead', 'object' => 'not_anonymous', 'objectId' => 567];
        $logs[] = $expectedLogs[] = ['bundle' => 'lead', 'object' => 'is_anonymous', 'objectId' => 678];
        $logs[] = ['bundle' => 'object', 'object' => 'exception', 'objectId' => 789];

        $this->auditLogModel->expects(self::once())
          ->method('getLogForObject')
          ->with(null, null, null, 19)
          ->willReturn($logs);

        $nullObjectModel = $this->createMock(AbstractCommonModel::class);
        $nullObjectModel->expects(self::once())
          ->method('getEntity')
          ->with(123)
          ->willReturn(null);
        $nonFormModel = $this->createMock(AbstractCommonModel::class);
        $nonFormModel->expects(self::once())
          ->method('getEntity')
          ->with(234)
          ->willReturn($this->createMock(CommonEntity::class));
        $nonEntityHasNoGetter = $this->createMock(FormModel::class);
        $nonEntityHasNoGetter->expects(self::once())
          ->method('getEntity')
          ->with(345)
          ->willReturn($this->createMock(FormEntity::class));
        $notLead       = $this->createMock(FormModel::class);
        $anonymousUser = $this->createMock(User::class);
        $anonymousUser->method('getName')->willReturn('mautic.lead.lead.anonymous');
        $notLead->expects(self::once())
          ->method('getEntity')
          ->with(456)
          ->willReturn($anonymousUser);
        $notLead->method('getNameGetter')
          ->willReturn('getName');
        $notAnonymous = $this->createMock(FormModel::class);
        $adminUser    = $this->createMock(User::class);
        $adminUser->method('getName')->willReturn('admin');
        $notAnonymous->expects(self::once())
          ->method('getEntity')
          ->with(567)
          ->willReturn($adminUser);
        $notAnonymous->method('getNameGetter')
          ->willReturn('getName');
        $isAnonymous = $this->createMock(FormModel::class);
        $isAnonymous->expects(self::once())
          ->method('getEntity')
          ->with(678)
          ->willReturn($anonymousUser);
        $isAnonymous->method('getNameGetter')
          ->willReturn('getName');
        $exception = $this->createMock(FormModel::class);
        $exception->expects(self::once())
          ->method('getEntity')
          ->with(789)
          ->willThrowException($this->createMock(\Exception::class));

        $this->modelFactory->expects(self::exactly(7))
          ->method('getModel')
          ->willReturnMap([
            ['null.object', $nullObjectModel],
            ['model.not_form_model', $nonFormModel],
            ['model.has_no_getter', $nonEntityHasNoGetter],
            ['item.not_lead', $notLead],
            ['lead.not_anonymous', $notAnonymous],
            ['lead.is_anonymous', $isAnonymous],
            ['object.exception', $exception],
          ]);

        $route           = $this->createMock(Route::class);
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects(self::exactly(5)) // no null object and  exception object
        ->method('get')
          ->withConsecutive(
            ['mautic_model_action'],
            ['mautic_model_action'],
            ['mautic_item_action'],
            ['mautic_lead_action'],
            ['mautic_lead_action'],
          )
          ->willReturnOnConsecutiveCalls(null, $route, $route, $route, null);

        $this->router->expects(self::exactly(5))
          ->method('getRouteCollection')
          ->willReturn($routeCollection);
        $this->router->expects(self::exactly(3))
          ->method('generate')
          ->willReturnMap([
            ['mautic_model_action', ['objectAction' => 'view', 'objectId' => 345], UrlGeneratorInterface::ABSOLUTE_PATH, '/not-getter'],
            ['mautic_item_action', ['objectAction' => 'view', 'objectId' => 456], UrlGeneratorInterface::ABSOLUTE_PATH, '/not-lead'],
            ['mautic_lead_action', ['objectAction' => 'view', 'objectId' => 567], UrlGeneratorInterface::ABSOLUTE_PATH, '/not-anonymous'],
          ]);

        $iconEvent = new IconEvent($this->security);
        $this->dispatcher->expects(self::once())
          ->method('dispatch')
          ->with(CoreEvents::FETCH_ICONS, $iconEvent);

        $expectedLogs[1]['objectName'] = 'object-123'; // null object
        $expectedLogs[1]['route']      = false;
        $expectedLogs[2]['objectName'] = ''; // not form model
        $expectedLogs[2]['route']      = false;
        $expectedLogs[3]['objectName'] = ''; // has no getter
        $expectedLogs[3]['route']      = '/not-getter';
        $expectedLogs[4]['objectName'] = 'mautic.lead.lead.anonymous';  // not lead
        $expectedLogs[4]['route']      = '/not-lead';
        $expectedLogs[5]['objectName'] = 'admin';  // not anonymous
        $expectedLogs[5]['route']      = '/not-anonymous';
        $expectedLogs[6]['objectName'] = 'whatever';  // is anonymous (translated)
        $expectedLogs[6]['route']      = false;

        $this->event->expects(self::once())
          ->method('setTemplateData')
          ->with(['logs' => $expectedLogs, 'icons' => []]);

        $subscriber = new DashboardSubscriber(
          $this->auditLogModel,
          $this->translator,
          $this->router,
          $this->security,
          $this->dispatcher,
          $this->modelFactory
        );
        $subscriber->onWidgetDetailGenerate($this->event);
    }
}
