<?php

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class EmailGraphStatsControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testTemplateViewAction(): void
    {
        $email   = $this->createEmail('Email A', 'Email A Subject', 'template', 'beefree-empty', 'Test html');
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/emails-graph-stats/{$email->getId()}/0/2022-08-21/2022-09-21");
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    public function testSegmentViewAction(): void
    {
        $segment        = $this->createSegment('Segment B', 'segment-B');
        $email          = $this->createEmail('Email B', 'Email B Subject', 'list', 'beefree-empty', 'Test html', $segment);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/emails-graph-stats/{$email->getId()}/0/2022-08-21/2022-09-21");
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setAlias($alias);
        $segment->setPublicName($name);
        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @param array<mixed>|null $varientSetting
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, ?LeadList $segment = null, ?array $varientSetting = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setVariantSettings($varientSetting);
        if (!empty($segment)) {
            $email->addList($segment);
        }
        $this->em->persist($email);

        return $email;
    }
}
