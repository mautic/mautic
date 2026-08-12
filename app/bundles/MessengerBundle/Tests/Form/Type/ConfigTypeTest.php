<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('database')]
final class ConfigTypeTest extends MauticMysqlTestCase
{
    private const string MULTIPLIER_FIELD = 'config[messengerconfig][messenger_retry_strategy_multiplier]';

    /**
     * The multiplier uses GreaterThanOrEqual(1); the other retry fields use
     * GreaterThanOrEqual(0), so this exact message is unique to the multiplier.
     */
    private const string MIN_MULTIPLIER_ERROR = 'greater than or equal to 1';

    /**
     * Regression for https://github.com/mautic/mautic/issues/16017.
     *
     * Symfony's MultiplierRetryStrategy throws an InvalidArgumentException at
     * runtime for any multiplier < 1, which previously crashed the queue worker
     * once the Queue Settings form had been saved with `0`. The form must
     * reject sub-1 multipliers with a validation error.
     *
     * @param numeric-string $value
     */
    #[DataProvider('belowOneMultiplierProvider')]
    public function testMultiplierBelowOneIsRejected(string $value): void
    {
        $this->assertStringContainsString(self::MIN_MULTIPLIER_ERROR, $this->submitMultiplier($value), 'A multiplier below 1 must be rejected with a validation error.');
    }

    /**
     * @return iterable<string, array{numeric-string}>
     */
    public static function belowOneMultiplierProvider(): iterable
    {
        yield 'zero'     => ['0'];
        yield 'fraction' => ['0.5'];
    }

    public function testValidMultiplierIsAccepted(): void
    {
        $this->assertStringNotContainsString(self::MIN_MULTIPLIER_ERROR, $this->submitMultiplier('3'), 'A multiplier >= 1 must not trigger the minimum-multiplier validation error.');
    }

    /**
     * Submits the configuration form with the given retry-strategy multiplier
     * and returns the resulting response HTML.
     */
    private function submitMultiplier(string $value): string
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

        return $this->client->getResponse()->getContent() ?: '';
    }
}
