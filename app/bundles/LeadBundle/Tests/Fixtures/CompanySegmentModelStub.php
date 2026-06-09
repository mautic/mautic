<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Fixtures;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CompanySegmentModelStub extends CompanySegmentModel
{
    public function testDispatchEvent(string $action, FormEntity $entity, bool $isNew = false, ?Event $event = null): ?Event
    {
        return $this->dispatchEvent($action, $entity, $isNew, $event);
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }
}
