<?php

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LeadModelFunctionalTest extends MauticMysqlTestCase
{
    private $pointsAdded = false;

    protected $useCleanupRollback = false;

    public function testSavingPrimaryCompanyAfterPointsAreSetByListenerAreNotResetToDefaultOf0BecauseOfPointsFieldDefaultIs0(): void
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(LeadEvents::LEAD_POST_SAVE, [$this, 'addPointsListener']);

        /** @var LeadModel $model */
        $model = static::getContainer()->get('mautic.lead.model.lead');
        /** @var EntityManager $em */
        $em   = static::getContainer()->get('doctrine.orm.entity_manager');

        // Set company to trigger setPrimaryCompany()
        $lead = new Lead();
        $data = ['email' => 'pointtest@test.com', 'company' => 'PointTest'];
        $model->setFieldValues($lead, $data, false, true, true);

        // Save to trigger points listener and setting primary company
        $model->saveEntity($lead);

        // Clear from doctrine memory so we get a fresh entity to ensure the points are definitely saved
        $em->detach($lead);
        $lead = $model->getEntity($lead->getId());

        $this->assertEquals(10, $lead->getPoints());
    }

    /**
     * Simulate a PointModel::triggerAction.
     */
    public function addPointsListener(LeadEvent $event): void
    {
        // Prevent a loop
        if ($this->pointsAdded) {
            return;
        }

        $this->pointsAdded = true;

        $lead = $event->getLead();
        $lead->adjustPoints(10);

        /** @var LeadModel $model */
        $model = static::getContainer()->get('mautic.lead.model.lead');
        $model->saveEntity($lead);
    }

    public function testMultipleAssignedCompany(): void
    {
        self::assertEquals(2, count($this->getContactWithAssignTwoCompanies()));
    }

    public function testSignleAssignedCompany(): void
    {
        $this->setUpSymfony(array_merge($this->configParams, ['contact_allow_multiple_companies' => 0]));

        self::assertEquals(1, count($this->getContactWithAssignTwoCompanies()));
    }

    /**
     * @return array<int,array<int|string>>
     *
     * @throws DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getContactWithAssignTwoCompanies(): array
    {
        $company = new Company();
        $company->setName('Doe Corp');

        $this->em->persist($company);

        $company2 = new Company();
        $company2->setName('Doe Corp 2');

        $this->em->persist($company2);

        $contact = new Lead();
        $contact->setEmail('test@test.com');

        $this->em->persist($contact);
        $this->em->flush();

        /** @var LeadModel $leadModel */
        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');
        $leadModel->addToCompany($contact, $company);
        $leadModel->addToCompany($contact, $company2);

        /** @var CompanyLeadRepository $companyLeadRepo */
        $companyLeadRepo  = $this->em->getRepository(CompanyLead::class);
        $contactCompanies = $companyLeadRepo->getCompaniesByLeadId($contact->getId());

        return $contactCompanies;
    }

    public function testGetCustomLeadFieldLength(): void
    {
        $leadModel  = $this->getContainer()->get('mautic.lead.model.lead');
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');

        // Create a lead field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias('custom_field_len_test')
            ->setType('string')
            ->setObject('lead')
            ->setCharLengthLimit(150);
        $fieldModel->saveEntity($leadField);

        // Create leads without adding value to the 'Test field'.
        $bob = new Lead();
        $bob->setFirstname('Bob')
            ->setLastname('Smith')
            ->setEmail('bob.smith@test.com');
        $leadModel->saveEntity($bob);

        $jane = new Lead();
        $jane->setFirstname('Jane')
            ->setLastname('Smith')
            ->setEmail('jane.smith@test.com');
        $leadModel->saveEntity($jane);

        $this->em->clear();

        // Custom field is empty, and will return null.
        $length = $leadModel->getCustomLeadFieldLength([$leadField->getAlias()]);
        $this->assertNull($length[$leadField->getAlias()]);

        // Update lead Bob with 'Test field' value.
        $hashStringBob = hash('sha256', __METHOD__);
        $bob->addUpdatedField($leadField->getAlias(), $hashStringBob);
        $leadModel->saveEntity($bob);

        // Update lead Jane with 'Test field' value.
        $hashStringJane = hash('sha1', __METHOD__);
        $jane->addUpdatedField($leadField->getAlias(), $hashStringJane);
        $leadModel->saveEntity($jane);

        $this->em->clear();

        $length = $leadModel->getCustomLeadFieldLength([$leadField->getAlias()]);
        $this->assertEquals(strlen($hashStringBob), $length[$leadField->getAlias()]);
    }

    public function testGettingUnknownCustomFieldLength(): void
    {
        $this->expectException(DBALException::class);

        $leadModel  = $this->getContainer()->get('mautic.lead.model.lead');
        $leadModel->getCustomLeadFieldLength(['unknown_field']);
    }
}
