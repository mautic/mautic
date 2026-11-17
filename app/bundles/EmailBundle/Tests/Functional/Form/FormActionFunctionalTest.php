<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Form;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Tests\Model\FormSubmissionTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

final class FormActionFunctionalTest extends MauticMysqlTestCase
{
    use FormSubmissionTrait;

    protected $useCleanupRollback = false;

    public function testDNCContactEmail(): void
    {
        // email to send after submit
        $email = $this->createEmail('Send in response', 'Send in response');
        $this->em->flush();

        // payload to create form with field and action
        $payload = [
            'name'        => 'dnctest',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
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
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'actions' => [
                [
                    'name'        => 'Send email to contact',
                    'description' => 'action description',
                    'type'        => 'email.send.lead',
                    'order'       => 1,
                    'properties'  => [
                        'email'    => $email->getId(),
                    ],
                ],
            ],
        ];

        // Create a form
        [$formId, $formAlias] = $this->createForm($payload);

        // Create a landing Page having form
        $page = $this->createLandingPage('Page with dnc form', 'page-with-dnc-form', sprintf('<p>{form=%s}</p>', $formId));

        // create lead
        $lead = $this->createLead();

        // create email to send form
        $emailToSendForm = $this->createEmail(
            'Landing page email with form',
            sprintf('This is the link: <a title="Page Link" href="%s://%s/%s">page-with-dnc-form</a> ', $this->client->getRequest()->getScheme(), $this->client->getRequest()->getHost(), $page->getAlias())
        );

        $this->em->flush();

        // email send to contact
        $emailModel = self::getContainer()->get(EmailModel::class);
        $this->assertInstanceOf(EmailModel::class, $emailModel);
        $emailModel->sendEmail($emailToSendForm, [[
            'id'           => $lead->getId(),
            'email'        => $lead->getEmail(),
            'firstname'    => $lead->getFirstname(),
            'lastname'     => $lead->getLastname(), ]]
        );

        $this->leadAddToDNC($lead);

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        // submit form directly
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this->assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[email]'           => $lead->getEmail(),
            'mauticform[firstname]'       => $lead->getFirstname(),
            'mauticform[lastname]'        => $lead->getLastname(),
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead'  => $lead->getId(),
            ]
        );

        // unsubscribed contacts still receive transactional form emails (ignoreDNC => true)
        $this->assertInstanceOf(Stat::class, $emailStat);
    }

    public function testBouncedContactDoesNotReceiveEmail(): void
    {
        // email to send after submit
        $email = $this->createEmail('Send in response bounced', 'Send in response bounced');
        $this->em->flush();

        // payload to create form with field and action
        $payload = [
            'name'        => 'bouncedtest',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test for bounced contact',
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
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'actions' => [
                [
                    'name'        => 'Send email to contact',
                    'description' => 'action description',
                    'type'        => 'email.send.lead',
                    'order'       => 1,
                    'properties'  => [
                        'email'    => $email->getId(),
                    ],
                ],
            ],
        ];

        // Create a form
        [$formId, $formAlias] = $this->createForm($payload);

        // Create a landing Page having form
        $page = $this->createLandingPage('Page with bounced form', 'page-with-bounced-form', sprintf('<p>{form=%s}</p>', $formId));

        // create lead
        $lead = $this->createLead('bounced@example.com');

        // create email to send form
        $emailToSendForm = $this->createEmail(
            'Landing page email with bounced form',
            sprintf('This is the link: <a title="Page Link" href="%s://%s/%s">page-with-bounced-form</a> ', $this->client->getRequest()->getScheme(), $this->client->getRequest()->getHost(), $page->getAlias())
        );

        $this->em->flush();

        // email send to contact
        $emailModel = self::getContainer()->get(EmailModel::class);
        $this->assertInstanceOf(EmailModel::class, $emailModel);
        $emailModel->sendEmail($emailToSendForm, [[
            'id'           => $lead->getId(),
            'email'        => $lead->getEmail(),
            'firstname'    => $lead->getFirstname(),
            'lastname'     => $lead->getLastname(), ]]
        );

        $this->leadAddToDNC($lead, DoNotContact::BOUNCED);

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        // submit form directly
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->selectButton('Submit');
        $this->assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[email]'           => $lead->getEmail(),
            'mauticform[firstname]'       => $lead->getFirstname(),
            'mauticform[lastname]'        => $lead->getLastname(),
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead'  => $lead->getId(),
            ]
        );

        // bounced contacts do NOT receive form emails (even with ignoreDNC => true)
        $this->assertNotInstanceOf(Stat::class, $emailStat);
    }

    private function createLandingPage(string $title, string $alias, string $html): Page
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setDateAdded(new \DateTime());
        $page->setTitle($title);
        $page->setAlias($alias);
        $page->setTemplate('Blank');
        $page->setCustomHtml($html);

        $this->em->persist($page);

        return $page;
    }

    public function createEmail(string $name, string $html): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setCustomHTML($html);

        $this->em->persist($email);

        return $email;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function createLead(string $email = 'j@doe.com'): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $lead->setLastname('Doe');
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function leadAddToDNC(Lead $lead, int $reason = DoNotContact::UNSUBSCRIBED): void
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel('email');
        $doNotContact->setReason($reason);

        $this->em->persist($doNotContact);
        $this->em->flush();
    }
}
