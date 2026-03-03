<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class ProjectEntityLookupLimitTest extends MauticMysqlTestCase
{
    private const LOOKUP_CHOICE_LIST_URL = '/s/ajax?action=project:getLookupChoiceList';

    /**
     * Create 2000 test emails.
     */
    private function createTestEmails(): void
    {
        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get(EmailModel::class);

        for ($i = 1; $i <= 2000; ++$i) {
            $email = new Email();
            $email->setName('Common Autocomplete Email '.$i);
            $email->setSubject('Subject '.$i);
            $email->setEmailType('template');
            $email->setTemplate('blank');
            $emailModel->saveEntity($email);
        }
    }

    /**
     * Test: AJAX search with a common keyword
     * Must return exactly 1000 results.
     */
    public function testAjaxSearchReturnsExactly1000Results(): void
    {
        // Arrange: seed 2000 emails
        $this->createTestEmails();

        // Act: trigger AJAX lookup endpoint
        $this->client->request('GET', self::LOOKUP_CHOICE_LIST_URL, [
            'entityType' => 'email',
            'filter'     => 'Common',
        ]);

        // Assert: response is valid
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $decoded = json_decode($response->getContent(), true);
        Assert::assertIsArray($decoded, 'Response must be a JSON array.');

        $count = count($decoded);

        // ✅ Must return exactly 1000 items
        Assert::assertSame(
            1000,
            $count,
            "AJAX autocomplete search should return exactly 1000 results, got {$count}."
        );
    }
}
