<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

final class DynamicContentTokenReplacementTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;

    #[\PHPUnit\Framework\Attributes\DataProvider('dwcTokenDataProvider')]
    public function testDwcTokenReplacement(string $entityName): void
    {
        $lead1 = $this->createLead('Pune');
        $lead2 = $this->createLead('Jaipur');

        $this->createDynamicContent('DC-1', 'slot-Name', 0);
        $token   = '<div data-slot="dwc" data-param-slot-name="slot-Name">Default content goes here</div>';
        $content = '<html><body><p>'.$token.'</p></body></html>';

        $functionName = 'create'.ucfirst($entityName);
        $entity       = $this->{$functionName}($content);

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);
        $this->assertContent('/'.$entityName.'/preview/'.$entity->getId().'?contactId='.$lead1->getId(), 'some content');
        $this->assertContent('/'.$entityName.'/preview/'.$entity->getId().'?contactId='.$lead2->getId(), 'Default content goes here');
    }

    private function assertContent(string $url, string $expectedContent): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $url
        );
        $response = $this->client->getResponse();
        $this->assertStringContainsString($expectedContent, (string) $response->getContent());
    }

    private function createEmail(string $content): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSubject('subject');
        $email->setCustomHtml($content);
        $email->setTemplate('mautic_code_mode');
        $email->setEmailType('template');
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createPage(string $content): Page
    {
        $page = new Page();
        $page->setTemplate('mautic_code_mode');
        $page->setTitle('Test');
        $page->setAlias('Test');
        $page->setCustomHtml($content);
        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    private function createLead(string $city): Lead
    {
        $lead = new Lead();
        $lead->setEmail($city.'@someemail.com');
        $lead->setCity($city);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @return iterable<int, array{string}>
     */
    public static function dwcTokenDataProvider(): iterable
    {
        yield ['email'];
        yield ['page'];
    }
}
