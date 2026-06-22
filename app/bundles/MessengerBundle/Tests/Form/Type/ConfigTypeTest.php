<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class ConfigTypeTest extends MauticMysqlTestCase
{
    private const MULTIPLIER_FIELD = 'config[messengerconfig][messenger_retry_strategy_multiplier]';

    /**
     * Regression for https://github.com/mautic/mautic/issues/16017.
     *
     * Symfony's MultiplierRetryStrategy throws an InvalidArgumentException at
     * runtime for any multiplier < 1, which previously crashed the queue worker
     * once the Queue Settings form had been saved with `0`. The form must reject
     * sub-1 multipliers instead of persisting them.
     *
     * @param numeric-string $value
     */
    #[DataProvider('belowOneMultiplierProvider')]
    public function testMultiplierBelowOneIsRejected(string $value): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('config[buttons][save]')->form();
        $form->setValues([
            'config[coreconfig][site_url]' => 'https://mautic-community.local', // required
            self::MULTIPLIER_FIELD         => $value,
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        // Re-open the form: the invalid value must not have been persisted, and
        // whatever remains stored must still satisfy the multiplier >= 1 rule.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $stored = (float) $crawler->selectButton('config[buttons][save]')->form()[self::MULTIPLIER_FIELD]->getValue();
        Assert::assertNotSame((float) $value, $stored, 'A multiplier below 1 must be rejected, not saved.');
        Assert::assertGreaterThanOrEqual(1.0, $stored, 'The persisted multiplier must remain >= 1.');
    }

    /**
     * @return iterable<string, array{numeric-string}>
     */
    public static function belowOneMultiplierProvider(): iterable
    {
        yield 'zero'     => ['0'];
        yield 'fraction' => ['0.5'];
    }

    public function testValidMultiplierIsSaved(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('config[buttons][save]')->form();
        $form->setValues([
            'config[coreconfig][site_url]' => 'https://mautic-community.local', // required
            self::MULTIPLIER_FIELD         => '3',
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        $this->assertResponseIsSuccessful();

        $stored = (float) $crawler->selectButton('config[buttons][save]')->form()[self::MULTIPLIER_FIELD]->getValue();
        Assert::assertSame(3.0, $stored, 'A multiplier >= 1 must be saved.');
    }
}
