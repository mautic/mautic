<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Event\TrackingPreferenceChangeEvent;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ContactTrackingPreferenceHelperFunctionalTest extends MauticMysqlTestCase
{
    // The test needs a real rollback of the helper's own transaction
    protected $useCleanupRollback = false;

    /**
     * Otherwise the stored choice would stop a second try from forgetting the contact's tracking identifiers.
     */
    public function testChoiceIsNotStoredWhenAListenerFails(): void
    {
        $contact = new Lead();
        $contact->setEmail('contact@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        self::getContainer()->get(EventDispatcherInterface::class)->addListener(
            TrackingPreferenceChangeEvent::TRACKING_PREFERENCE_CHANGE,
            static fn () => throw new \RuntimeException('Listener failed'),
            10
        );

        try {
            self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, false, TrackingPreference::SOURCE_PREFERENCE_CENTRE);
            $this->fail('The failure of the listener should reach the caller.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Listener failed', $e->getMessage());
        }

        $this->assertSame(
            0,
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.TrackingPreference::TABLE_NAME.' WHERE lead_id = ?', [$contact->getId()]),
            'A choice was stored although a listener failed.'
        );
    }
}
