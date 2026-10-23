<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class KioskModeSubmissionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function kioskModeProvider(): iterable
    {
        yield 'kiosk mode does not store the IP or device' => [true, false];
        yield 'regular form stores the IP and device' => [false, true];
    }

    #[DataProvider('kioskModeProvider')]
    public function testSubmissionStoresIpAddressAndDeviceIdOnlyOutsideKioskMode(bool $inKioskMode, bool $expectTracking): void
    {
        $formId = $this->createForm($inKioskMode);

        $this->logoutUser();
        $this->submitForm($formId, 'kiosk@example.com');

        $this->em->clear();
        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        $submission = $submissions[0];

        $this->assertSame($expectTracking, null !== $submission->getIpAddress());
        $this->assertSame($expectTracking, null !== $submission->getTrackingId());
        $this->assertSame('kiosk@example.com', $submission->getLead()?->getEmail());
    }

    #[DataProvider('kioskModeProvider')]
    public function testIpAddressIsSavedOnlyOutsideKioskMode(bool $inKioskMode, bool $expectTracking): void
    {
        $formId = $this->createForm($inKioskMode);
        $this->createFormSubmitPointAction($formId);

        $this->logoutUser();
        $this->submitForm($formId, 'kiosk@example.com', '203.0.113.7');

        $this->em->clear();
        $ipAddress = $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.7']);
        $this->assertSame($expectTracking, null !== $ipAddress);

        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'kiosk@example.com']);
        $this->assertInstanceOf(Lead::class, $contact);
        $this->assertSame(5, $contact->getPoints());

        $pointsChangeLog = $this->em->getRepository(PointsChangeLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(PointsChangeLog::class, $pointsChangeLog);
        $this->assertSame($expectTracking, null !== $pointsChangeLog->getIpAddress());

        $pointLog = $this->em->getRepository(LeadPointLog::class)->findOneBy(['lead' => $contact]);
        $this->assertInstanceOf(LeadPointLog::class, $pointLog);
        $this->assertSame($expectTracking, null !== $pointLog->getIpAddress());
    }

    #[DataProvider('kioskModeProvider')]
    public function testPointsActionStoresIpAddressOnlyOutsideKioskMode(bool $inKioskMode, bool $expectTracking): void
    {
        $formId = $this->createForm($inKioskMode);
        $this->addFormActions($formId, ['lead.pointschange' => ['operator' => 'plus', 'points' => 3]]);

        $this->logoutUser();
        $this->submitForm($formId, 'kiosk@example.com', '203.0.113.8');

        $this->em->clear();
        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'kiosk@example.com']);
        $this->assertInstanceOf(Lead::class, $contact);
        $this->assertSame(3, $contact->getPoints());

        $pointsChangeLog = $this->em->getRepository(PointsChangeLog::class)->findOneBy(['lead' => $contact, 'type' => 'form']);
        $this->assertInstanceOf(PointsChangeLog::class, $pointsChangeLog);
        $this->assertSame($expectTracking, null !== $pointsChangeLog->getIpAddress());
        $this->assertSame($expectTracking, null !== $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.8']));
    }

    /**
     * Without contact fields the kiosk submission has no contact, so the actions must not fall back to the contact the device cookie points to.
     */
    public function testContactActionsSkipTheTrackedContactWhenKioskSubmissionHasNoContact(): void
    {
        $segment = new LeadList();
        $segment->setName('Kiosk segment');
        $segment->setPublicName('Kiosk segment');
        $segment->setAlias('kiosk-segment');
        $this->em->persist($segment);
        $this->em->flush();

        $formId = $this->createForm(true, [[
            'label' => 'Comment',
            'type'  => 'text',
            'alias' => 'comment',
        ]]);
        $this->addFormActions($formId, [
            'lead.pointschange' => ['operator' => 'plus', 'points' => 3],
            'lead.changetags'   => ['add_tags' => ['kiosk'], 'remove_tags' => []],
            'lead.changelist'   => ['addToLists' => [$segment->getId()], 'removeFromLists' => []],
            'lead.updatelead'   => ['lastname' => 'Updated'],
        ]);
        $trackedContact = $this->createTrackedVisitor();
        $contactCount   = $this->em->getRepository(Lead::class)->count([]);

        $this->submitForm($formId, null, '203.0.113.8', ['mauticform[comment]' => 'Hello']);

        $this->em->clear();
        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        $this->assertNotInstanceOf(Lead::class, $submissions[0]->getLead());
        $this->assertSame($contactCount, $this->em->getRepository(Lead::class)->count([]));

        $trackedContact = $this->em->getRepository(Lead::class)->find($trackedContact->getId());
        $this->assertInstanceOf(Lead::class, $trackedContact);
        $this->assertSame(0, $trackedContact->getPoints());
        $this->assertCount(0, $trackedContact->getTags());
        $this->assertNull($trackedContact->getLastname());
        $this->assertNotInstanceOf(ListLead::class, $this->em->getRepository(ListLead::class)->findOneBy(['lead' => $trackedContact, 'list' => $segment->getId()]));
        $this->assertNotInstanceOf(PointsChangeLog::class, $this->em->getRepository(PointsChangeLog::class)->findOneBy(['lead' => $trackedContact]));
        $this->assertNotInstanceOf(IpAddress::class, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.8']));
    }

    private function createTrackedVisitor(): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Trackedvisitor');
        $this->em->persist($contact);

        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId('kiosktrackingid000000000');
        $device->setDateAdded(new \DateTime());
        $this->em->persist($device);
        $this->em->flush();

        $this->logoutUser();
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->set(new Cookie('mautic_device_id', 'kiosktrackingid000000000'));
        $cookieJar->set(new Cookie('mtc_id', (string) $contact->getId()));

        return $contact;
    }

    /**
     * @param array<string, array<string, mixed>> $actions properties keyed by action type
     */
    private function addFormActions(int $formId, array $actions): void
    {
        $form  = $this->em->getReference(Form::class, $formId);
        $order = 0;
        foreach ($actions as $type => $properties) {
            $action = new Action();
            $action->setForm($form);
            $action->setName($type);
            $action->setType($type);
            $action->setProperties($properties);
            $action->setOrder(++$order);
            $this->em->persist($action);
        }
        $this->em->flush();
        $this->em->clear();
    }

    private function createFormSubmitPointAction(int $formId): void
    {
        $point = new Point();
        $point->setName('Submits the kiosk form');
        $point->setType('form.submit');
        $point->setDelta(5);
        $point->setRepeatable(false);
        $point->setIsPublished(true);
        $point->setProperties(['forms' => [$formId]]);
        $this->em->persist($point);
        $this->em->flush();
    }

    /**
     * @param array<int, array<string, string>> $fields
     */
    private function createForm(bool $inKioskMode, array $fields = [[
        'label'        => 'Email',
        'type'         => 'email',
        'alias'        => 'email',
        'mappedObject' => 'contact',
        'mappedField'  => 'email',
    ]]): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'        => 'Kiosk form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'inKioskMode' => $inKioskMode,
            'fields'      => $fields,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, (string) $this->client->getResponse()->getContent());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame($inKioskMode, (bool) ($response['form']['inKioskMode'] ?? false));

        return (int) $response['form']['id'];
    }

    /**
     * @param array<string, string> $values
     */
    private function submitForm(int $formId, ?string $email, string $remoteAddress = '127.0.0.1', array $values = []): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[id=mauticform_kioskform]')->form();
        $form->setValues(null === $email ? $values : ['mauticform[email]' => $email] + $values);
        $this->client->submit($form, [], ['REMOTE_ADDR' => $remoteAddress]);
        $this->assertResponseIsSuccessful();
    }
}
