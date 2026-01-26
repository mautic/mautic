<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\Assert;

final class ProjectEntityLookupLimitTest extends MauticMysqlTestCase
{
    private const LOOKUP_CHOICE_LIST_URL = '/s/ajax?action=project:getLookupChoiceList';

    /**
     * Test: AJAX search with a common keyword
     * Must return exactly 1000 results.
     */
    public function testAjaxSearchReturnsExactly1000Results(): void
    {
        // Arrange: seed 2000 emails
        $this->createTestEmails(2000);

        // Act: trigger AJAX lookup endpoint
        $this->client->request('GET', self::LOOKUP_CHOICE_LIST_URL, [
            'entityType' => 'email',
            'filter'     => 'Common',
        ]);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $decoded  = json_decode($response->getContent(), true);
        Assert::assertIsArray($decoded, 'Response must be a JSON array.');
        Assert::assertCount(
            1000,
            $decoded,
            'AJAX autocomplete search should return exactly 1000 results'
        );
    }

    /**
     * Create test emails.
     */
    private function createTestEmails(int $limit): void
    {
        /** @var EmailModel $emailModel */
        $emailModel = self::getContainer()->get(EmailModel::class);

        for ($i = 1; $i <= $limit; ++$i) {
            $email = new Email();
            $email->setName('Common Autocomplete Email '.$i);
            $email->setSubject('Subject '.$i);
            $email->setEmailType('template');
            $email->setTemplate('blank');
            $emailModel->saveEntity($email);
        }
    }
}
