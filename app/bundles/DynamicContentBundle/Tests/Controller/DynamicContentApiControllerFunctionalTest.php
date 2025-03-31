<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\IsolatedTestTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentLeadData;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class DynamicContentApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use IsolatedTestTrait;

    public function testDwcGetEndpointForNoSlotNorContact(): void
    {
        $this->client->request(Request::METHOD_GET, '/dwc/slot-a');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getContent());
    }

    public function testDwcGetEndpointForASlotAndContact(): void
    {
        $contact = new Lead();
        $contact->setEmail('johana@doe.email');

        $dwc = new DynamicContent();
        $dwc->setContent('<some>content</some>');
        $dwc->setName('Slot A');
        $dwc->setSlotName('slot-a');

        $dwcContact = new DynamicContentLeadData();
        $dwcContact->setDateAdded(new \DateTime());
        $dwcContact->setDynamicContent($dwc);
        $dwcContact->setLead($contact);
        $dwcContact->setSlot($dwc->getSlotName());

        $stat = new Stat();
        $stat->setLead($contact);
        $stat->setTrackingHash('tracking-hash-1');
        $stat->setEmailAddress($contact->getEmail());
        $stat->setDateSent(new \DateTime());

        $this->em->persist($contact);
        $this->em->persist($stat);
        $this->em->persist($dwc);
        $this->em->persist($dwcContact);
        $this->em->flush();

        $ct = ClickthroughHelper::encodeArrayForUrl(['stat' => 'tracking-hash-1']);

        $this->client->request(Request::METHOD_GET, "/dwc/slot-a?ct={$ct}");

        self::assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $responseArray = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertSame('<some>content</some>', $responseArray['content']);
    }

    public function testCreateDwc(): void
    {
        $payload = [
            'name'    => 'API test',
            'content' => 'API test',
        ];

        $this->client->request(Request::METHOD_POST, '/api/dynamiccontents/new', $payload);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, $this->client->getResponse()->getContent());
    }
}
