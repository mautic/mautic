<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Event;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\TestCase;

final class TokenReplacementEventTest extends TestCase
{
    public function testGetPassthrough(): void
    {
        $passthrough = ['passthrough'];
        $event       = new TokenReplacementEvent('', null, [], $passthrough);
        $this->assertSame($passthrough, $event->getPassthrough());
    }

    public function testGetSetContent(): void
    {
        $content = 'content';
        $event   = new TokenReplacementEvent($content);
        $this->assertSame($content, $event->getContent());
    }

    public function testAddGetTokens(): void
    {
        $token  = 'token';
        $value  = 'value';
        $token1 = 'token1';
        $value1 = 'value1';
        $event  = new TokenReplacementEvent('');
        $this->assertSame([], $event->getTokens());
        $event->addToken($token, $value);
        $this->assertSame([$token => $value], $event->getTokens());
        $event->addToken($token1, $value1);
        $this->assertSame([
            $token  => $value,
            $token1 => $value1,
        ], $event->getTokens());
    }

    public function testGetClickthrough(): void
    {
        $leadId           = 1;
        $leadEntity['id'] = $leadId;
        $clickthrough     = ['lead' => $leadEntity];
        $event            = new TokenReplacementEvent('', $leadEntity, $clickthrough);
        $this->assertSame(['lead' => 1], $event->getClickthrough());

        $leadEntity   = new Lead();

        $clickthrough = ['lead', $leadEntity];
        $event        = new TokenReplacementEvent('', $leadEntity, $clickthrough);
        $this->assertSame($clickthrough, $event->getClickthrough());

        $leadEntity->setId($leadId);
        $clickthrough = ['lead' => $leadEntity];
        $event        = new TokenReplacementEvent('', $leadEntity, $clickthrough);
        $this->assertSame(['lead' => 1], $event->getClickthrough());
    }

    public function testGetEntity(): void
    {
        $lead  = new Lead();
        $event = new TokenReplacementEvent($lead);
        $this->assertSame($lead, $event->getEntity());
    }

    public function testSetClickthrough(): void
    {
        $lead         = new Lead();
        $event        = new TokenReplacementEvent($lead);
        $clickthrough = ['clickthrough'];
        $event->setClickthrough($clickthrough);

        $this->assertSame($clickthrough, $event->getClickthrough());
    }

    public function testGetLead(): void
    {
        $lead  = null;
        $event = new TokenReplacementEvent('', $lead);
        $this->assertSame($lead, $event->getLead());
        $lead  = new Lead();
        $event = new TokenReplacementEvent('', $lead);
        $this->assertSame($lead, $event->getLead());
    }

    public function testSetContent(): void
    {
        $content1 = 'content1';
        $event    = new TokenReplacementEvent('');
        $event->setContent($content1);
        $this->assertSame($content1, $event->getContent());
    }
}
