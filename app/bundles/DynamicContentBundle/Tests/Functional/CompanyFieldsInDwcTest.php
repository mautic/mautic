<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class CompanyFieldsInDwcTest extends MauticMysqlTestCase
{
    public function testCompanyFieldsAreAvailableInFilters(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/new');
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $options = $crawler->filter('#available_filters')->html();
        $this->assertStringContainsString('<optgroup label="Primary company">', $options, 'The company group should be present.');
    }

    /**
     * @return iterable<string,array{bool,mixed[]}>
     */
    public static function dataCompanyFieldsAreFollowedWhenEmailIsSent(): iterable
    {
        yield 'Equal ZIP code matches' => [true, [
            [
                'glue'     => 'and',
                'field'    => 'companyzipcode',
                'object'   => 'company',
                'type'     => 'text',
                'filter'   => '12345',
                'display'  => null,
                'operator' => '=',
            ],
        ]];
        yield 'Equal ZIP code does not match' => [false, [
            [
                'glue'     => 'and',
                'field'    => 'companyzipcode',
                'object'   => 'company',
                'type'     => 'text',
                'filter'   => '56789',
                'display'  => null,
                'operator' => '=',
            ],
        ]];
        yield 'Not equal ZIP code matches' => [true, [
            [
                'glue'     => 'and',
                'field'    => 'companyzipcode',
                'object'   => 'company',
                'type'     => 'text',
                'filter'   => '56789',
                'display'  => null,
                'operator' => '!=',
            ],
        ]];
        yield 'Not equal ZIP code does not match' => [false, [
            [
                'glue'     => 'and',
                'field'    => 'companyzipcode',
                'object'   => 'company',
                'type'     => 'text',
                'filter'   => '12345',
                'display'  => null,
                'operator' => '!=',
            ],
        ]];
    }

    /**
     * @param mixed[] $filters
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataCompanyFieldsAreFollowedWhenEmailIsSent')]
    public function testCompanyFieldsAreFollowedWhenEmailIsSent(bool $shouldMatch, array $filters): void
    {
        $dynamicContent = $this->createDynamicContent($filters);
        $contact        = $this->createContactWithCompany();
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]' => 'Admin',
            'lead_quickemail[from]'     => 'admin@test.mail',
            'lead_quickemail[subject]'  => 'Some subject',
            'lead_quickemail[body]'     => sprintf('<html><body><p>{dwc=%s}</p></body></html>', $dynamicContent->getSlotName()),
            'lead_quickemail[list]'     => 0,
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $email = $this->getMailerMessages()[0]->getHtmlBody();

        if ($shouldMatch) {
            $this->assertStringContainsString($dynamicContent->getContent(), (string) $email);
        } else {
            $this->assertStringNotContainsString($dynamicContent->getContent(), (string) $email);
        }
    }

    /**
     * @param mixed[] $filters
     */
    private function createDynamicContent(array $filters): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('Name');
        $dynamicContent->setContent('DWC content');
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName('slot-name');
        $dynamicContent->setFilters($filters);
        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }

    private function createContactWithCompany(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('carl@fox.tld');
        $this->em->persist($contact);

        $company = new Company();
        $company->setName('Company name');
        $company->setZipcode('12345');
        $this->em->persist($company);

        $relation = new CompanyLead();
        $relation->setLead($contact);
        $relation->setCompany($company);
        $relation->setPrimary(true);
        $relation->setDateAdded(new \DateTime());
        $this->em->persist($relation);

        return $contact;
    }
}
