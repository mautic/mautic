<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;

class LeadDetailFunctionalTest extends MauticMysqlTestCase
{
    public function testCustomFieldOrderIsRespected(): void
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $lead->setLastname('Doe');
        $lead->setEmail('john@his-site.com');
        $this->em->persist($lead);

        $fieldRepository = $this->em->getRepository(LeadField::class);

        /** @var LeadField[] $fields */
        $fields = $fieldRepository->findBy(['object' => 'lead', 'group' => 'core'], [
            'label' => 'desc',
            'id'    => 'desc',
        ]);
        $order = 0;

        // re-order fields by the label
        foreach ($fields as $field) {
            $field->setOrder(++$order);
            $this->em->persist($field);
        }

        $this->em->flush();
        $this->em->clear();

        // initialize lead fields to adjust the expected core labels
        $lead->setFields([
            'core' => [
                'First Name' => [
                    'value' => 'John',
                ],
                'Last Name' => [
                    'value' => 'Doe',
                ],
                'Email' => [
                    'value' => 'john@his-site.com',
                ],
                'Primary company' => [
                    'value' => null,
                ],
                'Points' => [
                    'value' => 0,
                ],
            ],
        ]);
        $leadFields = array_filter($lead->getFields(true), fn ($value) => isset($value['value']));
        $leadFields = array_keys($leadFields);

        // get expected core labels
        $expectedLabels = $this->connection->createQueryBuilder()
            ->select('label')
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields')
            ->where('object = "lead"')
            ->andWhere('field_group = "core"')
            ->andWhere('label IN (:leadFields)')
            ->orderBy('field_order')
            ->setParameter(
                'leadFields',
                $leadFields,
                ArrayParameterType::STRING
            )
            ->executeQuery()
            ->fetchFirstColumn();

        $expectedLabels = array_merge(['Created on', 'ID'], $expectedLabels);

        $crawler = $this->client->request('GET', sprintf('/s/contacts/view/%d', $lead->getId()));

        // get actual core labels
        $actualLabels = $crawler->filter('#lead-details table')
            ->first()
            ->filter('td:first-child')
            ->extract(['_text']);
        $actualLabels = array_map('trim', $actualLabels);

        Assert::assertSame($expectedLabels, $actualLabels);
    }

    public function testLeadViewPreventsXSS(): void
    {
        $firstName = 'aaa" onmouseover=alert(1) a="';
        $lead      = new Lead();
        $lead->setFirstname($firstName);
        $this->em->persist($lead);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/contacts/view/%d', $lead->getId()));

        $anchorTag  = $crawler->filter('#toolbar ul.dropdown-menu-right li')->first()->filter('a');
        $mouseOver  = $anchorTag->attr('onmouseover');
        $dataHeader = $anchorTag->attr('data-header');

        Assert::assertNull($mouseOver);
        Assert::assertSame(sprintf('Campaigns %s is part of', $firstName), $dataHeader);
        $response = $this->client->getResponse();
        // Make sure the data-target-url is not an absolute URL
        Assert::assertStringContainsString(sprintf('data-target-url="/s/contacts/view/%s/stats"', $lead->getId()), $response->getContent());
    }
}
