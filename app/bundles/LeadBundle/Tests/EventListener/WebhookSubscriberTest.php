<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\EventListener\WebhookSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WebhookSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testNewContactEventIsFiredWhenIdentified()
    {
        $dispatcher = new EventDispatcher();

        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    function ($type) {
                        return LeadEvents::LEAD_POST_SAVE.'_new' === $type;
                    }
                )
            );

        $webhookSubscriber = new WebhookSubscriber();
        $webhookSubscriber->setFactory($mockFactory);
        $webhookSubscriber->setWebhookModel($mockModel);

        $dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        $lead->setDateIdentified(new \DateTime());
        $event = new LeadEvent($lead, true);
        $dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }

    public function testUpdateContactEventIsFiredWhenUpdatedButWithoutDateIdentified()
    {
        $dispatcher = new EventDispatcher();

        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel->expects($this->once())
            ->method('queueWebhooksByType')
            ->with(
                $this->callback(
                    function ($type) {
                        return LeadEvents::LEAD_POST_SAVE.'_update' === $type;
                    }
                )
            );

        $webhookSubscriber = new WebhookSubscriber();
        $webhookSubscriber->setFactory($mockFactory);
        $webhookSubscriber->setWebhookModel($mockModel);

        $dispatcher->addSubscriber($webhookSubscriber);

        $lead = new Lead();
        $lead->setEmail('hello@hello.com');
        // remove date identified so it'll simulate a simple update
        $lead->resetChanges();
        $event = new LeadEvent($lead, false);
        $dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }

    public function testWebhookIsNotDeliveredIfContactIsAVisitor()
    {
        $dispatcher = new EventDispatcher();

        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder(WebhookModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel->expects($this->exactly(0))
            ->method('queueWebhooksByType');

        $webhookSubscriber = new WebhookSubscriber();
        $webhookSubscriber->setFactory($mockFactory);
        $webhookSubscriber->setWebhookModel($mockModel);

        $dispatcher->addSubscriber($webhookSubscriber);

        $lead  = new Lead();
        $event = new LeadEvent($lead, false);
        $dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);
    }
}
