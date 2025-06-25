<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class CampaignActionAnonymizeUserDataSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useCleanupRollback = false;
        $this->setUpSymfony($this->configParams);
    }
    public const LEAD_DEFAULT_DEFINES = [
        'firstname' => 'Test',
        'lastname'  => 'User',
        'city'      => 'City',
        'zipcode'   => 'Zipcode',
        'address1'  => 'Address 1',
        'address2'  => 'Address 2',
        'instagram' => 'Instagram',
        'fax'       => 'Fax',
        'twitter'   => 'Twitter',
        'linkedin'  => 'LinkedIn',
        'company'   => 'Company',
    ];

    public function testRunCampaignWithAnonymizeUserDataAction(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $campaign           = $this->createCampaign();
        $event              = $this->createEvent($campaign);
        $preDefLead1        = 'Foo';
        $preDefLead2        = 'Bar';
        $company1           = $this->createCompany();
        $company2           = $this->createCompany('Company 2', 'foobaa2@mauit.com');

        $newCompany1 = $this->em->getRepository(Company::class)->find($company1->getId());
        $this->assertNotNull($newCompany1);

        $lead1              = $this->createLead($preDefLead1);
        $this->addCompanyOnLead($lead1, $company1, true);
        $this->addCompanyOnLead($lead1, $company2, false);

        $lead2              = $this->createLead($preDefLead2);
        $this->addCompanyOnLead($lead2, $company2, true);

        $this->createLeadCampaign($campaign, $lead1);
        $this->createLeadCampaign($campaign, $lead2);

        // Execute the campaign.
        $this->testSymfonyCommand('mautic:campaigns:update');
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign With Anonymize User Data');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        // Fields: Firstname, Lastname, Address Line 1, Instagram, Email, Company Description
        $fieldsToAnonymize = ['2', '3', '11', '25', '6', '43'];
        // Fields: Position, Address Line 2, Company Address 1
        $fieldsToDelete = ['5', '12', '29'];
        // Create event: Anonymize User Data
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Anonymize User Data');
        $event->setType('lead.action_anonymizeuserdata');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $event->setProperties([
            'pseudonymize'      => '1',
            'fieldsToAnonymize' => $fieldsToAnonymize,
            'fieldsToDelete'    => $fieldsToDelete,
        ]);
        $event->setDecisionPath('yes');
        $event->setOrder(1);

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createLead(string $preDefinition): Lead
    {
        $lead = new Lead();
        $lead->setEmail($preDefinition.'test@test.com');
        $lead->setFirstname($preDefinition.' Test');
        $lead->setLastname($preDefinition.' User');
        $lead->setCity($preDefinition.' City');
        $lead->setZipcode($preDefinition.' Zipcode');
        $lead->setAddress1($preDefinition.self::LEAD_DEFAULT_DEFINES['address1']);
        $lead->setAddress2($preDefinition.' Address 2');
        $fields = [
            'position'  => $preDefinition.' Position',
            'instagram' => $preDefinition.' Instagram',
            'twitter'   => $preDefinition.' Twitter',
            'linkedin'  => $preDefinition.' LinkedIn',
            'company'   => $preDefinition.' Company',
        ];

        $this->em->getRepository(Lead::class)->saveEntity($lead);
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $leadModel->setFieldValues($lead, $fields);

        return $lead;
    }

    private function createLeadCampaign(Campaign $campaign, Lead $lead): CampaignLead
    {
        // Create Campaign Lead
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());

        $this->em->persist($campaignLead);
        $this->em->flush();

        return $campaignLead;
    }

    private function createCompany(string $name = 'Company', string $email='company@foobaa.com'): Company
    {
        $leadFields = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => 'companyaddress1']);

        $company = new Company();
        $company->setName($name);
        $company->setDescription('Company Description');
        $company->setIndustry('Industry');
        $company->setWebsite('www.company.com');
        $company->setEmail($email);
        $company->setPhone('1234567890');
        $company->setAddress1('Company Address 1');
        $company->setAddress2('Company Address 2');
        $company->setCity('Company City');

        $companyModel = static::getContainer()->get('mautic.lead.model.company');
        assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);
        $companyModel->setFieldValues($company, []);
        $companyModel->setFieldValues($company, [
            'companyaddress1' => [
                'value' => 'Company Address 1',
                'label' => 'Company Address 1',
            ],
            'companyaddress2' => [
                'value' => 'Company Address 2',
                'label' => 'Company Address 2',
            ],
            'companydescription111' => [
                'value' => 'Company Description',
                'label' => 'Company Description',
            ],
        ]);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    /**
     * @return array<string, Lead|Company|CompanyLead>
     */
    private function addCompanyOnLead(Lead $lead, Company $company, bool $primaryCompany = true): array
    {
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setPrimary($primaryCompany);
        $companyLead->setDateAdded(new \DateTime());
        $lead->setPrimaryCompany($company);
        $lead->setCompany($company);
        $this->em->persist($companyLead);
        $this->em->persist($lead);
        $this->em->persist($company);
        $this->em->flush();

        return [
            'lead'        => $lead,
            'company'     => $company,
            'companyLead' => $companyLead,
        ];
    }
}
