<?php

namespace MauticPlugin\MauticCrmBundle\Command;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeletion;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessPipedriveDeletionsCommand extends ContainerAwareCommand
{
    const PERSON_ENTITY_TYPE       = 'person';
    const LEAD_ENTITY_TYPE         = 'lead';
    const ORGANIZATION_ENTITY_TYPE = 'organization';
    const COMPANY_ENTITY_TYPE      = 'company';

    /** @var EntityManager */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:integration:pipedrive:process-deletions')
            ->setDescription('Processes the Pipedrive deletion queue');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io         = new SymfonyStyle($input, $output);
        $container  = $this->getContainer();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = $container->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject(PipedriveIntegration::INTEGRATION_NAME);

        if (!$integrationObject->getIntegrationSettings()->getIsPublished()) {
            $io->note('Pipedrive integration id disabled.');

            return;
        }

        $this->em = $container->get('doctrine.orm.default_entity_manager');
        $query    = $this->em->createQuery('SELECT d FROM MauticPlugin\MauticCrmBundle\Entity\PipedriveDeletion d WHERE d.deletedDate < :olderThan');
        $query->setParameter('olderThan', new \DateTime('-1 minute'));
        $deletions = $query->getResult();

        $deleted = 0;

        /** @var PipedriveDeletion $deletion */
        foreach ($deletions as $deletion) {
            $integrationEntity = null;
            $type              = $deletion->getObjectType();

            if ('lead' === $type) {
                $integrationEntity = $this->getLeadIntegrationEntity(['integrationEntityId' => $deletion->getIntegrationEntityId()]);
            } elseif ('company' === $type) {
                $integrationEntity = $this->getCompanyIntegrationEntity(['integrationEntityId' => $deletion->getIntegrationEntityId()]);
            }

            if (!$integrationEntity) {
                $io->note('Integration entity not found, skipping.');
                continue;
            }

            $entityClass = 'company' === $type ? Company::class : Lead::class;
            /** @var Company|Lead $entity */
            $entity = $this->em->getRepository($entityClass)->findOneById($integrationEntity->getInternalEntityId());

            if (!$entity) {
                $name = 'company' === $type ? 'Company' : 'Lead';
                $io->note($name.' doesn\'t exist.');
                continue;
            }

            // prevent listeners from exporting
            $entity->setEventData('pipedrive.webhook', 1);
            /** @var ModelFactory $modelFactory */
            $modelFactory = $container->get('mautic.model.factory');
            /** @var CompanyModel|LeadModel $model */
            $modelType = $type;

            if ('company' === $modelType) {
                $modelType = 'lead.company';
            }

            $model = $modelFactory->getModel($modelType);
            $model->deleteEntity($entity);

            if (!empty($entity->deletedId)) {
                $this->em->remove($integrationEntity);
                $this->em->remove($deletion);
                ++$deleted;
            }
        }

        $io->success('Deleted '.$deleted.' items. Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));
    }

    /**
     * @return IntegrationEntity|object|null
     */
    protected function getLeadIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::PERSON_ENTITY_TYPE;
        $criteria['internalEntity']    = self::LEAD_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    /**
     * @return IntegrationEntity|object|null
     */
    protected function getCompanyIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::ORGANIZATION_ENTITY_TYPE;
        $criteria['internalEntity']    = self::COMPANY_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    /**
     * @return IntegrationEntity|object|null
     */
    private function getIntegrationEntity(array $criteria = [])
    {
        $criteria['integration'] = PipedriveIntegration::INTEGRATION_NAME;

        return $this->em->getRepository(IntegrationEntity::class)->findOneBy($criteria);
    }
}
