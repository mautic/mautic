<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Entity\FieldChange;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\Assert;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LeadSubscriberTest extends MauticMysqlTestCase
{
    private EventDispatcherInterface $dispatcher;

    private FieldChangeRepository $fieldChangeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher            = static::getContainer()->get('event_dispatcher');
        $this->fieldChangeRepository = $this->em->getRepository(FieldChange::class);

        static::getContainer()->set(
            'mautic.integrations.helper.sync_integrations',
            new class extends SyncIntegrationsHelper {
                public function __construct()
                {
                }

                public function hasObjectSyncEnabled(string $object): bool
                {
                    return true;
                }

                /**
                 * @return array<int,string>
                 */
                public function getEnabledIntegrations(): array
                {
                    return ['unicorn'];
                }
            }
        );
    }

    public function testContactPostSaveWithProxy(): void
    {
        // The contact must exist in the database in order to create a reference later.
        $contactReal = new Lead();
        $this->em->persist($contactReal);
        $this->em->flush();
        $this->em->clear();

        // By getting a reference we'll get a proxy class instead of the real entity class.
        /** @var Lead $contactProxy */
        $contactProxy = $this->em->getReference(Lead::class, $contactReal->getId());
        $contactProxy->__set('email', 'john@doe.email');
        $contactProxy->setPoints(100);
        $event = new LeadEvent($contactProxy, true);

        $this->dispatcher->dispatch($event, LeadEvents::LEAD_POST_SAVE);

        $fieldChanges = $this->fieldChangeRepository->findChangesForObject('unicorn', Lead::class, $contactReal->getId());
        Assert::assertCount(2, $fieldChanges, print_r($fieldChanges, true));

        Assert::assertSame('unicorn', $fieldChanges[0]['integration']);
        Assert::assertSame($contactReal->getId(), (int) $fieldChanges[0]['object_id']);
        Assert::assertSame(Lead::class, $fieldChanges[0]['object_type']);
        Assert::assertSame('email', $fieldChanges[0]['column_name']);
        Assert::assertSame('string', $fieldChanges[0]['column_type']);
        Assert::assertSame('john@doe.email', $fieldChanges[0]['column_value']);

        Assert::assertSame('unicorn', $fieldChanges[1]['integration']);
        Assert::assertSame($contactReal->getId(), (int) $fieldChanges[1]['object_id']);
        Assert::assertSame(Lead::class, $fieldChanges[1]['object_type']);
        Assert::assertSame('points', $fieldChanges[1]['column_name']);
        Assert::assertSame('int', $fieldChanges[1]['column_type']);
        Assert::assertSame('100', $fieldChanges[1]['column_value']);
    }
}
