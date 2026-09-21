<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Model\ProjectModel;

final class ProjectPopoverSecurityTest extends MauticMysqlTestCase
{
    public function testProjectPopoverEscapesProjectNameAndDescription(): void
    {
        $xssNamePayload        = '<img src=x onerror=alert("project-name-xss-marker")>';
        $xssDescriptionPayload = '<script>alert("project-desc-script-marker")</script><img src=x onerror=alert("project-desc-onerror-marker")>';

        /** @var ProjectModel $projectModel */
        $projectModel = self::getContainer()->get(ProjectModel::class);
        $project      = new Project();
        $project->setName($xssNamePayload);
        $project->setDescription($xssDescriptionPayload);
        $projectModel->saveEntity($project);

        $email = new Email();
        $email->setName('Project Popover Security Email');
        $email->setSubject('Project Popover Security Email');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->addProject($project);

        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get(EmailModel::class);
        $emailModel->saveEntity($email);

        $this->client->request('GET', '/s/emails/view/'.$email->getId());
        $this->assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();

        $this->assertStringContainsString('class="project-popover"', $content);
        // Raw unencoded payloads must never appear verbatim in the server response
        $this->assertStringNotContainsString($xssNamePayload, $content);
        $this->assertStringNotContainsString($xssDescriptionPayload, $content);
        // Description: striptags preserves inner text of <script> but strips the <img onerror> entirely
        $this->assertStringContainsString('project-desc-script-marker', $content);
        $this->assertStringNotContainsString('project-desc-onerror-marker', $content);
    }
}
