<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CompanyImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompanyModel $companyModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onCompanyExport', 0],
            EntityImportEvent::class => ['onCompanyImport', 0],
        ];
    }

    public function onCompanyExport(EntityExportEvent $event): void
    {
        if (Company::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $companyId = $event->getEntityId();
        $company   = $this->companyModel->getEntity($companyId);
        if (!$company) {
            return;
        }

        $companyData = [
            'id'                        => $company->getId(),
            'is_published'              => $company->isPublished(),
            'social_cache'              => $company->getSocialCache(),
            'score'                     => $company->getScore(),
            'companyaddress1'           => $company->getAddress1(),
            'companyaddress2'           => $company->getAddress2(),
            'companyphone'              => $company->getPhone(),
            'companycity'               => $company->getCity(),
            'companystate'              => $company->getState(),
            'companyzipcode'            => $company->getZipcode(),
            'companycountry'            => $company->getCountry(),
            'companywebsite'            => $company->getWebsite(),
            'companyindustry'           => $company->getIndustry(),
            'companydescription'        => $company->getDescription(),
            'companyname'               => $company->getName(),
        ];

        $event->addEntity(Company::ENTITY_NAME, $companyData);
    }

    public function onCompanyImport(EntityImportEvent $event): void
    {
        if (Company::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Companies will not have a created_by_user field set.');
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new Company();
            $object->setName($element['companyname']);
            $object->setDescription($element['companydescription'] ?? '');
            $object->setSocialCache($element['social_cache'] ?? null);
            $object->setScore($element['score'] ?? 0);
            $object->setAddress1($element['companyaddress1'] ?? '');
            $object->setAddress2($element['companyaddress2'] ?? '');
            $object->setPhone($element['companyphone'] ?? '');
            $object->setCity($element['companycity'] ?? '');
            $object->setState($element['companystate'] ?? '');
            $object->setZipcode($element['companyzipcode'] ?? '');
            $object->setCountry($element['companycountry'] ?? '');
            $object->setWebsite($element['companywebsite'] ?? '');
            $object->setIndustry($element['companyindustry'] ?? '');
            $object->setIsPublished((bool) $element['is_published']);
            $object->setDateAdded(new \DateTime());
            $object->setCreatedByUser($userName);
            $object->setDateModified(new \DateTime());

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $output->writeln('<info>Imported company: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
