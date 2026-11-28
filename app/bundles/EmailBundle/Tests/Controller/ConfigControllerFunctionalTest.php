<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testValuesAreEscapedProperly(): void
    {
        $data = [
            'scheme'   => 'smtp',
            'host'     => 'local+@$#/:*!host',
            'port'     => '25',
            'path'     => 'pa+@$#/:*!th',
            'user'     => 'us+@$#/:*!er',
            'password' => 'pass+@$#/:*!word',
            'type'     => 'ty+@$#/:*!pe',
        ];

        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        // set form data
        $form   = $crawler->selectButton('config[buttons][save]')->form();
        $values = $form->getPhpValues();

        $values['config']['emailconfig']['mailer_dsn']['scheme']                         = $data['scheme'];
        $values['config']['emailconfig']['mailer_dsn']['host']                           = $data['host'];
        $values['config']['emailconfig']['mailer_dsn']['port']                           = $data['port'];
        $values['config']['emailconfig']['mailer_dsn']['path']                           = $data['path'];
        $values['config']['emailconfig']['mailer_dsn']['user']                           = $data['user'];
        $values['config']['emailconfig']['mailer_dsn']['password']                       = $data['password'];
        $values['config']['emailconfig']['mailer_dsn']['options']['list']['0']['label']  = 'type';
        $values['config']['emailconfig']['mailer_dsn']['options']['list']['0']['value']  = $data['type'];

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        // check the DSN is escaped properly in the config file (both using double percent signs and URL encoded)
        $configParameters = $this->getConfigParameters();
        $this->assertSame($this->escape(
            $data['scheme']
            .'://'.urlencode($data['user'])
            .':'.urlencode($data['password'])
            .'@'.urlencode($data['host'])
            .':'.$data['port']
            .'/'.urlencode($data['path'])
            .'?type='.urlencode($data['type'])
        ), $configParameters['mailer_dsn']);

        // check values are unescaped properly in the edit form
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('config[buttons][save]')->form();
        $this->assertSame($data['scheme'], $form['config[emailconfig][mailer_dsn][scheme]']->getValue());
        $this->assertSame($data['host'], $form['config[emailconfig][mailer_dsn][host]']->getValue());
        $this->assertSame($data['port'], $form['config[emailconfig][mailer_dsn][port]']->getValue());
        $this->assertSame($data['path'], $form['config[emailconfig][mailer_dsn][path]']->getValue());
        $this->assertSame($data['user'], $form['config[emailconfig][mailer_dsn][user]']->getValue());
        $this->assertSame('🔒', $form['config[emailconfig][mailer_dsn][password]']->getValue());
        $this->assertSame($data['type'], $form['config[emailconfig][mailer_dsn][options][list][0][value]']->getValue());
    }

    public function testForwardMessageTextsAreSavedPerLanguage(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        $form   = $crawler->selectButton('config[buttons][save]')->form();
        $values = $form->getPhpValues();

        $values['config']['emailconfig']['forward_mail_subject']['list'] = [['label' => 'fr', 'value' => 'TR : |SUBJECT|']];
        $values['config']['emailconfig']['forward_mail_body']['list']    = [
            ['label' => 'fr', 'value' => "Cet e-mail pourrait t'intéresser : |URL|"],
            ['label' => 'de', 'value' => 'Schau mal: |URL|'],
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $configParameters = $this->getConfigParameters();
        $this->assertSame(['fr' => 'TR : |SUBJECT|'], $configParameters['forward_mail_subject']);
        $this->assertSame(['fr', 'de'], array_keys($configParameters['forward_mail_body']));
        $this->assertSame('Schau mal: |URL|', $configParameters['forward_mail_body']['de']);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('config[buttons][save]')->form();
        $this->assertSame('fr', $form['config[emailconfig][forward_mail_body][list][0][label]']->getValue());
        $this->assertSame("Cet e-mail pourrait t'intéresser : |URL|", $form['config[emailconfig][forward_mail_body][list][0][value]']->getValue());

        $translator = self::getContainer()->get(TranslatorInterface::class);
        $this->assertSame($translator->trans('mautic.email.config.forward_mail.language'), $crawler->filter('#config_emailconfig_forward_mail_body_list_0_label')->attr('placeholder'));
        $this->assertSame($translator->trans('mautic.email.config.forward_mail_body.value'), $crawler->filter('#config_emailconfig_forward_mail_body_list_0_value')->attr('placeholder'));
    }

    /**
     * @return iterable<string, array{array{label: string, value: string}}>
     */
    public static function invalidForwardMessageTextProvider(): iterable
    {
        yield 'a language that does not exist' => [['label' => 'fe', 'value' => 'Regarde : |URL|']];
        yield 'a language without a text' => [['label' => 'fr', 'value' => '']];
    }

    /**
     * @param array{label: string, value: string} $row
     */
    #[DataProvider('invalidForwardMessageTextProvider')]
    public function testInvalidForwardMessageTextIsNotSaved(array $row): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        $form   = $crawler->selectButton('config[buttons][save]')->form();
        $values = $form->getPhpValues();

        $values['config']['emailconfig']['forward_mail_body']['list'] = [$row];
        $savedBefore                                                  = $this->getConfigParameters()['forward_mail_body'] ?? [];

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $label = self::getContainer()->get(TranslatorInterface::class)->trans('mautic.email.config.forward_mail_body');
        $this->assertCount(1, $crawler->filter('.has-error > label')->reduce(fn ($node): bool => str_contains($node->text(), $label)), 'The list is marked as invalid.');
        $this->assertSame($savedBefore, $this->getConfigParameters()['forward_mail_body'] ?? [], 'Nothing is saved.');
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('dataInvalidDsn')]
    public function testInvalidDsn(array $data, string $expectedMessage): void
    {
        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/emailconfig');
        self::assertResponseIsSuccessful();

        // set form data
        $form = $crawler->selectButton('config[buttons][save]')->form();
        $form->setValues($data);

        // check if there is the given validation error
        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString($expectedMessage, $crawler->text());
    }

    /**
     * @return iterable<string, mixed[]>
     */
    public static function dataInvalidDsn(): iterable
    {
        yield 'Unsupported scheme' => [
            [
                'config[emailconfig][mailer_dsn][scheme]' => 'unknown',
            ],
            'The "unknown" scheme is not supported.',
        ];

        yield 'Invalid DSN' => [
            [
                'config[emailconfig][mailer_dsn][scheme]' => 'smtp',
                'config[emailconfig][mailer_dsn][host]'   => '',
            ],
            'The mailer DSN is invalid.',
        ];
    }

    /**
     * @return mixed[]
     */
    private function getConfigParameters(): array
    {
        $parameters = [];
        /** @var \AppKernel $kernel */
        $kernel = self::getContainer()->get(KernelInterface::class);
        include $kernel->getLocalConfigFile();

        return $parameters;
    }

    private function escape(string $value): string
    {
        return str_replace('%', '%%', $value);
    }
}
