<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Model\ProjectModel;

final class ProjectRepositoryAssociationSearchFunctionalTest extends MauticMysqlTestCase
{
    public function testAssociationSearchCommandsFilterProjects(): void
    {
        $projectWithEmail    = $this->createProject('Project with email');
        $projectWithoutEmail = $this->createProject('Project without email');

        $email = new Email();
        $email->setName('Email association search test');
        $email->setSubject('Email association search test');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->addProject($projectWithEmail);

        $emailModel = self::getContainer()->get(EmailModel::class);
        $this->assertInstanceOf(EmailModel::class, $emailModel);
        $emailModel->saveEntity($email);

        $projectRepository = $this->em->getRepository(Project::class);
        $withEmails        = $projectRepository->getEntities([
            'filter'          => ['string' => 'has:emails'],
            'ignore_paginator' => true,
        ]);
        $withoutEmails = $projectRepository->getEntities([
            'filter'          => ['string' => '!has:emails'],
            'ignore_paginator' => true,
        ]);

        $this->assertContains($projectWithEmail, $withEmails);
        $this->assertNotContains($projectWithoutEmail, $withEmails);
        $this->assertContains($projectWithoutEmail, $withoutEmails);
        $this->assertNotContains($projectWithEmail, $withoutEmails);
    }

    public function testIsMineSearchCommandFiltersProjectsByOwner(): void
    {
        $ownProject   = $this->createProject('Owned project');
        $otherProject = $this->createProject('Other owner project');

        $this->em->getConnection()->update(MAUTIC_TABLE_PREFIX.Project::TABLE_NAME, ['created_by' => 999999], ['id' => $otherProject->getId()]);

        $this->client->request('GET', '/api/projects?search='.urlencode('is:mine'));

        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString($ownProject->getName(), $content);
        $this->assertStringNotContainsString($otherProject->getName(), $content);
    }

    public function testHasCampaignsIgnoresDeletedCampaigns(): void
    {
        $projectWithDeletedCampaign = $this->createProject('Project with deleted campaign');
        $projectWithCampaign        = $this->createProject('Project with campaign');

        $deletedCampaign = $this->createCampaign('Deleted campaign', $projectWithDeletedCampaign);
        $this->createCampaign('Active campaign', $projectWithCampaign);
        $this->em->getConnection()->update(MAUTIC_TABLE_PREFIX.Campaign::TABLE_NAME, ['deleted' => date('Y-m-d H:i:s')], ['id' => $deletedCampaign->getId()]);

        $projectRepository = $this->em->getRepository(Project::class);
        $withCampaigns     = $projectRepository->getEntities([
            'filter'           => ['string' => 'has:campaigns'],
            'ignore_paginator' => true,
        ]);

        $this->assertContains($projectWithCampaign, $withCampaigns);
        $this->assertNotContains($projectWithDeletedCampaign, $withCampaigns);
    }

    private function createCampaign(string $name, Project $project): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name.' '.bin2hex(random_bytes(6)));
        $campaign->addProject($project);

        $campaignModel = self::getContainer()->get(CampaignModel::class);
        $this->assertInstanceOf(CampaignModel::class, $campaignModel);
        $campaignModel->saveEntity($campaign);

        return $campaign;
    }

    private function createProject(string $name): Project
    {
        $project = new Project();
        $project->setName($name.' '.bin2hex(random_bytes(6)));

        $projectModel = self::getContainer()->get(ProjectModel::class);
        $this->assertInstanceOf(ProjectModel::class, $projectModel);
        $projectModel->saveEntity($project);

        return $project;
    }
}
