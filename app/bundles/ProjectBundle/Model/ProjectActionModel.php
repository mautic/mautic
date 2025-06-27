<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\Entity\Project;

class ProjectActionModel
{
    public function __construct(
        private EntityManagerInterface $em,
        private CorePermissions $corePermissions,
    ) {
    }

    /**
     * Modify projects on multiple entities (add/remove).
     *
     * @param array<int> $entityIds
     * @param array<int> $addToProjects
     * @param array<int> $removeFromProjects
     *
     * @return array<int, object>
     */
    public function modifyProjectsOnEntities(array $entityIds, array $addToProjects, array $removeFromProjects, string $entityType): array
    {
        $entityClasses = [
            'email'    => \Mautic\EmailBundle\Entity\Email::class,
            'campaign' => \Mautic\CampaignBundle\Entity\Campaign::class,
            'form'     => \Mautic\FormBundle\Entity\Form::class,
            'asset'    => \Mautic\AssetBundle\Entity\Asset::class,
            'page'     => \Mautic\PageBundle\Entity\Page::class,
            'sms'      => \Mautic\SmsBundle\Entity\Sms::class,
            'message'  => \Mautic\ChannelBundle\Entity\Message::class,
            'leadlist' => \Mautic\LeadBundle\Entity\LeadList::class,
            'company'  => \Mautic\LeadBundle\Entity\Company::class,
        ];

        if (!isset($entityClasses[$entityType])) {
            return [];
        }

        $entityClass = $entityClasses[$entityType];
        $repository  = $this->em->getRepository($entityClass);
        $entities    = $repository->findBy(['id' => $entityIds]);

        // Get project entities to add and remove
        $projectsToAdd = [];
        if (!empty($addToProjects)) {
            $projectsToAdd = $this->em->getRepository(Project::class)->findBy(['id' => $addToProjects]);
        }

        $projectsToRemove = [];
        if (!empty($removeFromProjects)) {
            $projectsToRemove = $this->em->getRepository(Project::class)->findBy(['id' => $removeFromProjects]);
        }

        $affected = [];

        foreach ($entities as $entity) {
            if (!$this->canEdit($entity, $entityType)) {
                continue;
            }

            $modified = false;

            // Add projects
            foreach ($projectsToAdd as $project) {
                if (!$entity->getProjects()->contains($project)) {
                    $entity->addProject($project);
                    $modified = true;
                }
            }

            // Remove projects
            foreach ($projectsToRemove as $project) {
                if ($entity->getProjects()->contains($project)) {
                    $entity->removeProject($project);
                    $modified = true;
                }
            }

            if ($modified) {
                $affected[$entity->getId()] = $entity;
            }
        }

        if ($affected) {
            foreach ($affected as $entity) {
                $this->em->persist($entity);
            }
            $this->em->flush();
        }

        return $affected;
    }

    /**
     * Set project on multiple entities.
     *
     * @param array<int> $entityIds
     *
     * @return array<int, object>
     */
    public function setProjectOnEntities(array $entityIds, ?Project $project, string $entityType): array
    {
        $entityClasses = [
            'email'    => \Mautic\EmailBundle\Entity\Email::class,
            'campaign' => \Mautic\CampaignBundle\Entity\Campaign::class,
            'form'     => \Mautic\FormBundle\Entity\Form::class,
            'asset'    => \Mautic\AssetBundle\Entity\Asset::class,
            'page'     => \Mautic\PageBundle\Entity\Page::class,
            'sms'      => \Mautic\SmsBundle\Entity\Sms::class,
            'message'  => \Mautic\ChannelBundle\Entity\Message::class,
            'leadlist' => \Mautic\LeadBundle\Entity\LeadList::class,
            'company'  => \Mautic\LeadBundle\Entity\Company::class,
        ];

        if (!isset($entityClasses[$entityType])) {
            return [];
        }

        $entityClass = $entityClasses[$entityType];
        $repository  = $this->em->getRepository($entityClass);
        $entities    = $repository->findBy(['id' => $entityIds]);

        $affected = [];

        foreach ($entities as $entity) {
            if (!$this->canEdit($entity, $entityType)) {
                continue;
            }

            // Clear existing projects first
            $entity->getProjects()->clear();

            // Add the new project if provided
            if ($project) {
                $entity->addProject($project);
            }

            $affected[$entity->getId()] = $entity;
        }

        if ($affected) {
            foreach ($affected as $entity) {
                $this->em->persist($entity);
            }
            $this->em->flush();
        }

        return $affected;
    }

    private function canEdit(object $entity, string $entityType): bool
    {
        $permissionMap = [
            'email'    => ['email:emails:editown', 'email:emails:editother'],
            'campaign' => ['campaign:campaigns:editown', 'campaign:campaigns:editother'],
            'form'     => ['form:forms:editown', 'form:forms:editother'],
            'asset'    => ['asset:assets:editown', 'asset:assets:editother'],
            'page'     => ['page:pages:editown', 'page:pages:editother'],
            'sms'      => ['sms:smses:editown', 'sms:smses:editother'],
            'message'  => ['channel:messages:editown', 'channel:messages:editother'],
            'leadlist' => ['lead:lists:editown', 'lead:lists:editother'],
            'company'  => ['lead:leads:editown', 'lead:leads:editother'],
        ];

        if (!isset($permissionMap[$entityType])) {
            return false;
        }

        [$ownPermission, $otherPermission] = $permissionMap[$entityType];

        return $this->corePermissions->hasEntityAccess(
            $ownPermission,
            $otherPermission,
            method_exists($entity, 'getCreatedBy') ? $entity->getCreatedBy() : null
        );
    }
}
