<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Tests\_helpers\StageTestSeederTrait;

final class StageControllerTest extends MauticMysqlTestCase
{
    use StageTestSeederTrait;
    use UserEntityTrait;

    public function testIndexDisplaysContactCounts(): void
    {
        [$stageWithContacts, $stageWithoutContacts] = $this->seedStagesWithLeads();

        $this->client->request('GET', '/s/stages');

        $this->assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $stageWithContactsCount = $crawler->filter('#stage-contacts-count-'.$stageWithContacts->getId());
        $this->assertSame('a', $stageWithContactsCount->nodeName());
        $this->assertStringContainsString('2', $stageWithContactsCount->text());
        $this->assertStringContainsString('/s/contacts', (string) $stageWithContactsCount->attr('href'));

        $stageWithoutContactsCount = $crawler->filter('#stage-contacts-count-'.$stageWithoutContacts->getId());
        $this->assertSame('div', $stageWithoutContactsCount->nodeName());
        $this->assertNull($stageWithoutContactsCount->attr('href'));
    }

    public function testIndexRendersFunnelInStageWeightOrder(): void
    {
        $customer = (new Stage())
            ->setName('Customer')
            ->setIsPublished(true)
            ->setWeight(2);
        $lead = (new Stage())
            ->setName('Lead')
            ->setIsPublished(true)
            ->setWeight(1);

        $this->em->persist($customer);
        $this->em->persist($lead);
        $this->em->flush();

        $this->em->persist((new Lead())
            ->setFirstname('Lead')
            ->setLastname('One')
            ->setEmail('lead.one@example.com')
            ->setStage($lead));

        foreach (['one', 'two'] as $suffix) {
            $this->em->persist((new Lead())
                ->setFirstname('Customer')
                ->setLastname($suffix)
                ->setEmail("customer.{$suffix}@example.com")
                ->setStage($customer));
        }
        $this->em->flush();

        $this->client->request('GET', '/s/stages');

        $this->assertResponseIsSuccessful();

        $canvas = $this->client->getCrawler()->filter('canvas.funnel-chart');
        $this->assertCount(1, $canvas);

        $chart = json_decode($canvas->text(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['Lead', 'Customer'], $chart['labels']);
        $this->assertSame([1, 2], $chart['datasets'][0]['data']);
    }

    public function testIndexHidesFunnelWithoutViewOtherLeadPermission(): void
    {
        $this->seedStagesWithLeads();

        $user = $this->createUserWithPermission([
            'email'      => 'stage.viewer@mautic-test.com',
            'user-name'  => 'stage-viewer',
            'first-name' => 'Stage',
            'last-name'  => 'Viewer',
            'role'       => [
                'name'        => 'Stage viewer',
                'permissions' => [
                    'stage:stages:view'  => 4,
                    'lead:leads:viewown' => 2,
                ],
            ],
        ]);
        $this->em->flush();
        $this->loginOtherUser($user);

        $this->client->request('GET', '/s/stages');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->client->getCrawler()->filter('canvas.funnel-chart'));
    }
}
