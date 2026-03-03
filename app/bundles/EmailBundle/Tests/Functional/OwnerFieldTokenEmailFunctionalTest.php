<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class OwnerFieldTokenEmailFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

    public function testOwnerFieldTokensAreReplacedInSentEmailContent(): void
    {
        $role  = $this->createRole(sprintf('Owner Role %s', uniqid()));
        $owner = $this->createUser(
            sprintf('owner-%s@example.com', uniqid()),
            sprintf('owner-%s', uniqid()),
            'Contact',
            'Owner',
            $role
        );

        $lead = new Lead();
        $lead->setFirstname('Contact');
        $lead->setLastname('Receiver');
        $lead->setEmail(sprintf('contact-%s@example.com', uniqid()));
        $lead->setOwner($owner);

        $email = new Email();
        $email->setEmailType('list');
        $email->setName('Owner token email');
        $email->setSubject('Owner token email');
        $email->setCustomHtml(
            '<html><body>'
            .'Owner first name: {ownerfield=firstname} '
            .'Owner last name: {ownerfield=lastname} '
            .'Owner email: {ownerfield=email}'
            .'</body></html>'
        );

        $this->em->persist($lead);
        $this->em->persist($email);
        $this->em->flush();

        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get('mautic.email.model.email');
        $emailModel->sendEmail(
            $email,
            [
                [
                    'id'        => $lead->getId(),
                    'email'     => $lead->getEmail(),
                    'firstname' => $lead->getFirstname(),
                    'lastname'  => $lead->getLastname(),
                    'owner_id'  => $owner->getId(),
                ],
            ]
        );

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead'  => $lead->getId(),
            ]
        );
        Assert::assertNotNull($emailStat);

        $crawler = $this->client->request(Request::METHOD_GET, '/email/view/'.$emailStat->getTrackingHash());
        $body    = $crawler->filter('body');

        // Remove injected tracking tags for stable assertions.
        $body->filter('a,img,div')->each(function (Crawler $crawler): void {
            foreach ($crawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        $content = $body->html();

        Assert::assertStringContainsString('Owner first name: Contact', $content);
        Assert::assertStringContainsString('Owner last name: Owner', $content);
        Assert::assertStringContainsString('Owner email: '.$owner->getEmail(), $content);
        Assert::assertStringNotContainsString('{ownerfield=', $content);
    }
}
