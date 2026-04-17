<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Form;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FormActionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDNCContactEmail(): void
    {
        $email = $this->createEmailToSend();
        $lead  = $this->createLead();
        $this->addLeadToDnc($lead);
        [$formId, $formAlias] = $this->createFormWithSendEmailAction($email->getId());

        $this->submitForm($formId, $formAlias, [
            'mauticform[email]'     => $lead->getEmail(),
            'mauticform[firstname]' => $lead->getFirstname(),
            'mauticform[lastname]'  => $lead->getLastname(),
        ]);

        $emailStat = $this->em->getRepository(Stat::class)->findOneBy([
            'email' => $email,
            'lead'  => $lead,
        ]);

        Assert::assertNull($emailStat);
    }

    private function createEmailToSend(): Email
    {
        $email = new Email();
        $email->setName('Send in response');
        $email->setSubject('Send in response');
        $email->setCustomHtml('content');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $lead->setLastname('Doe');
        $lead->setEmail('john.doe.form.action@example.test');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function addLeadToDnc(Lead $lead): void
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel('email');
        $doNotContact->setReason(DoNotContact::UNSUBSCRIBED);

        $this->em->persist($doNotContact);
        $this->em->flush();
    }

    /**
     * @return array{int,string}
     */
    private function createFormWithSendEmailAction(int $emailId): array
    {
        $payload = [
            'name'        => 'dnctest',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
            ],
            'actions' => [
                [
                    'name'       => 'Send email to contact',
                    'type'       => 'email.send.lead',
                    'order'      => 1,
                    'properties' => [
                        'email' => $emailId,
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        return [$response['form']['id'], $response['form']['alias']];
    }

    /**
     * @param array<string,string> $values
     */
    private function submitForm(int $formId, string $formAlias, array $values): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();

        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this->assertCount(1, $formCrawler, $this->client->getResponse()->getContent());

        $form = $formCrawler->form();
        $form->setValues($values);
        $this->client->submit($form);

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }
}
