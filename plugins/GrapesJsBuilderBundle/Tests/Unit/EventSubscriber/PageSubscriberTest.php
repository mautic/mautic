<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageEvent;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\PageSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PageSubscriberTest extends TestCase
{
    /** @var MockObject&Config */
    private MockObject $config;
    /** @var MockObject&GrapesJsBuilderModel */
    private MockObject $model;
    private PageSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->config     = $this->createMock(Config::class);
        $this->model      = $this->createMock(GrapesJsBuilderModel::class);
        $this->subscriber = new PageSubscriber($this->config, $this->model);
    }

    public function testOnPagePostSaveSkipsWhenPluginNotPublished(): void
    {
        $this->config->method('isPublished')->willReturn(false);
        $this->model->expects(self::never())->method('addOrEditPageEntity');

        $this->subscriber->onPagePostSave(new PageEvent(new Page()));
    }

    public function testOnPagePostSaveCallsModelWhenPluginPublished(): void
    {
        $page = new Page();

        $this->config->method('isPublished')->willReturn(true);
        $this->model->expects(self::once())
            ->method('addOrEditPageEntity')
            ->with($page);

        $this->subscriber->onPagePostSave(new PageEvent($page));
    }
}
