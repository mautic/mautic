<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Tests\FormTestHelperTrait;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/** Characterization for private security investigation; not a fix regression test. */
final class FormEmailHtmlInjectionFunctionalTest extends MauticMysqlTestCase
{
    use FormTestHelperTrait;

    protected $useCleanupRollback = false;

    protected bool $authenticateApi = true;

    /** @return iterable<string, array{string, bool}> */
    public static function inputCases(): iterable
    {
        foreach (['form.email', 'email.send.lead', 'email.send.user'] as $action) {
            yield $action.' raw' => [$action, false];
            yield $action.' encoded' => [$action, true];
        }
    }

    #[DataProvider('inputCases')]
    public function testPublicTextFieldsInEmail(string $actionType, bool $encoded): void
    {
        $markup = '<strong data-probe="text-field">HTML-PROBE</strong>';
        $input  = $encoded ? htmlspecialchars($markup, ENT_QUOTES) : $markup;
        $body   = '<html><body><div id="mapped">{formfield=mapped}</div><div id="unmapped">{formfield=unmapped}</div><div id="contact">{contactfield=firstname}</div></body></html>';

        $email = new Email();
        $email->setName('Text field security characterization');
        $email->setSubject('Text field security characterization');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $email->setCustomHtml($body);
        $this->em->persist($email);
        $this->em->flush();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        self::assertInstanceOf(User::class, $user);
        $properties = match ($actionType) {
            'form.email' => ['to' => 'notification@example.test', 'subject' => 'Text field security characterization', 'message' => $body, 'copy_lead' => false, 'set_replyto' => false],
            'email.send.lead' => ['email' => $email->getId()],
            'email.send.user' => ['useremail' => ['email' => $email->getId()], 'user_id' => [$user->getId()]],
        };

        $form = $this->createFormViaApi([
            'name' => 'Text field security characterization',
            'formType' => 'standalone',
            'isPublished' => true,
            'fields' => [
                ['label' => 'Email', 'alias' => 'email', 'type' => 'email', 'mappedObject' => 'contact', 'mappedField' => 'email'],
                ['label' => 'Mapped', 'alias' => 'mapped', 'type' => 'text', 'mappedObject' => 'contact', 'mappedField' => 'firstname'],
                ['label' => 'Unmapped', 'alias' => 'unmapped', 'type' => 'text'],
                ['label' => 'Submit', 'type' => 'button'],
            ],
            'actions' => [['name' => 'Send email', 'type' => $actionType, 'properties' => $properties]],
        ]);

        // Remove both UI session and API credentials before the public submission.
        $this->logoutUser();
        $this->client->setServerParameter('PHP_AUTH_USER', '');
        $this->client->setServerParameter('PHP_AUTH_PW', '');
        $crawler = $this->client->request('GET', '/form/'.$form['id']);
        $publicForm = $crawler->filter('form')->form();
        $publicForm->setValues([
            'mauticform[email]' => 'submitter@example.test',
            'mauticform[mapped]' => $input,
            'mauticform[unmapped]' => $input,
        ]);
        $this->client->submit($publicForm);
        self::assertResponseIsSuccessful();

        $messages = self::getMailerMessages();
        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertInstanceOf(MauticMessage::class, $message);
        $html = $message->getHtmlBody();
        self::assertNotNull($html);
        $parsed = new Crawler($html);

        $submission = $this->em->getRepository(Submission::class)->findOneBy(['form' => $form['id']]);
        self::assertInstanceOf(Submission::class, $submission);
        $results = $submission->getResults();
        foreach (['mapped', 'unmapped'] as $alias) {
            self::assertSame($encoded ? $input : 'HTML-PROBE', $results[$alias]);
            self::assertCount($encoded && 'form.email' === $actionType ? 1 : 0, $parsed->filter('#'.$alias.' strong[data-probe="text-field"]'));
            self::assertSame($encoded && 'form.email' !== $actionType ? $markup : 'HTML-PROBE', $parsed->filter('#'.$alias)->text());
        }

        $directory = self::getContainer()->getParameter('kernel.project_dir').'/var/tmp/form-email-html-investigation';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $name = $actionType.($encoded ? '-encoded' : '-raw');
        file_put_contents($directory.'/'.$name.'.html', $html);
        file_put_contents($directory.'/'.$name.'.json', json_encode([
            'action' => $actionType,
            'input' => $input,
            'results' => $results,
            'contact_firstname' => $submission->getLead()->getFirstname(),
            'recipients' => array_map(static fn ($address): string => $address->getAddress(), $message->getTo()),
            'html' => $html,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
