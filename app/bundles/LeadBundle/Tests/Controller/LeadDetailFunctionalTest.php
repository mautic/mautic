<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadDetailFunctionalTest extends MauticMysqlTestCase
{
    // Creating a custom field alters the leads table (DDL), which commits the transaction, so rollback cleanup must be disabled.
    protected $useCleanupRollback = false;

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
        $leadFields = array_filter($lead->getFields(true), fn (array $value): bool => isset($value['value']));
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
        $actualLabels = array_map(trim(...), $actualLabels);

        $this->assertSame($expectedLabels, $actualLabels);
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

        $this->assertNull($mouseOver);
        $this->assertSame(sprintf('Campaigns for %s', $firstName), $dataHeader);
        $response = $this->client->getResponse();
        // Make sure the data-target-url is not an absolute URL
        $this->assertStringContainsString(sprintf('data-target-url="/s/contacts/view/%s/stats"', $lead->getId()), (string) $response->getContent());
    }

    public function testHtmlContactFieldIsPurifiedOnLeadDetailPage(): void
    {
        $htmlField = new LeadField();
        $htmlField->setName('HTML Bio')
            ->setAlias('html_bio')
            ->setType('html')
            ->setObject('lead')
            ->setIsPublished(true)
            ->setIsVisible(true);
        $htmlField->setGroup('core');

        $fieldModel = self::getContainer()->get(FieldModel::class);
        $this->assertInstanceOf(FieldModel::class, $fieldModel);
        $fieldModel->saveEntity($htmlField);

        $payload = '<p>Safe Bio</p><script>alert("xss")</script><img src=x onerror=alert(1)><a href="javascript:alert(2)">bad</a><a href="https://example.com">good</a>';

        $lead = new Lead();
        $lead->setEmail('html-field-xss@example.com');
        $lead->addUpdatedField('html_bio', $payload);

        $leadModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $leadModel);
        $leadModel->saveEntity($lead);

        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/contacts/view/%d', $lead->getId()));

        // The rendered custom-field value cell (the audit-trail table echoes the raw payload escaped, so scope to the field).
        $labelCell    = $crawler->filter('td span.fw-b')->reduce(fn (Crawler $node): bool => 'HTML Bio' === trim($node->text()));
        $fieldContent = $labelCell->count()
            ? $labelCell->closest('td')->nextAll()->first()->html()
            : '';

        // The sanitized output proves the field is no longer rendered with |raw.
        $sanitized = '<p>Safe Bio</p><img src="x" alt="x"><a>bad</a><a href="https://example.com">good</a>';

        $this->assertStringContainsString($sanitized, $fieldContent);
        $this->assertStringNotContainsString('<script>', $fieldContent);
    }

    public function testLeadDetailPageForSocialTabInDetailsCollapsibleForNoData(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.corp');
        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', 's/contacts/view/'.$contact->getId());
        $data    = $crawler->filterXPath('//div[@id="social"]//td');
        $this->assertCount(1, $data);

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $this->assertStringContainsString($translator->trans('mautic.lead.field.group.no_data'), $data->text());
    }

    public function testLeadDetailPageForSocialTabInDetailsCollapsible(): void
    {
        $crawler = $this->client->request('GET', 's/contacts/new/');

        $fbLink  = 'https://fb.com/john_doe_test';
        $form    = $crawler->selectButton('Save & Close')->form();
        $form->setValues(
            [
                'lead[firstname]' => 'John',
                'lead[lastname]'  => 'Doe',
                'lead[email]'     => 'john@doe.corp',
                'lead[facebook]'  => $fbLink,
            ]
        );

        $crawler = $this->client->submit($form);

        $fbProfile = $crawler->filterXPath('//div[@id="social"]//td[2]');

        $this->assertCount(1, $fbProfile);
        $this->assertStringContainsString($fbLink, $fbProfile->text());
    }
}
