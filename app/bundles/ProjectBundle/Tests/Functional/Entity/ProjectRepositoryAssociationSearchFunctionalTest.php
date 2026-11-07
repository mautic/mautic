<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Entity;

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
