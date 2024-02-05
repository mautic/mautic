<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ConfigControllerFunctionalTest extends MauticMysqlTestCase
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
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        // set form data
        $form   = $crawler->selectButton('config[buttons][save]')->form();
        $values = $form->getPhpValues();

        $values['config']['leadconfig']['contact_columns']                              = ['name', 'email', 'id']; // required
        $values['config']['emailconfig']['mailer_dsn']['scheme']                        = $data['scheme'];
        $values['config']['emailconfig']['mailer_dsn']['host']                          = $data['host'];
        $values['config']['emailconfig']['mailer_dsn']['port']                          = $data['port'];
        $values['config']['emailconfig']['mailer_dsn']['path']                          = $data['path'];
        $values['config']['emailconfig']['mailer_dsn']['user']                          = $data['user'];
        $values['config']['emailconfig']['mailer_dsn']['password']                      = $data['password'];
        $values['config']['emailconfig']['mailer_dsn']['options']['list']['0']['label'] = 'type';
        $values['config']['emailconfig']['mailer_dsn']['options']['list']['0']['value'] = $data['type'];

        $this->client->request($form->getMethod(), $form->getUri(), $values);
        Assert::assertTrue($this->client->getResponse()->isOk());

        // check the DSN is escaped properly in the config file (both using double percent signs and URL encoded)
        $configParameters = $this->getConfigParameters();
        Assert::assertSame($this->escape(
            $data['scheme']
            .'://'.urlencode($data['user'])
            .':'.urlencode($data['password'])
            .'@'.urlencode($data['host'])
            .':'.$data['port']
            .'/'.urlencode($data['path'])
            .'?type='.urlencode($data['type'])
        ), $configParameters['mailer_dsn']);

        // check values are unescaped properly in the edit form
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->selectButton('config[buttons][save]')->form();
        Assert::assertEquals($data['scheme'], $form['config[emailconfig][mailer_dsn][scheme]']->getValue());
        Assert::assertEquals($data['host'], $form['config[emailconfig][mailer_dsn][host]']->getValue());
        Assert::assertEquals($data['port'], $form['config[emailconfig][mailer_dsn][port]']->getValue());
        Assert::assertEquals($data['path'], $form['config[emailconfig][mailer_dsn][path]']->getValue());
        Assert::assertEquals($data['user'], $form['config[emailconfig][mailer_dsn][user]']->getValue());
        Assert::assertEquals('🔒', $form['config[emailconfig][mailer_dsn][password]']->getValue());
        Assert::assertEquals($data['type'], $form['config[emailconfig][mailer_dsn][options][list][0][value]']->getValue());
    }

    /**
     * @dataProvider dataInvalidDsn
     *
     * @param array<string, string> $data
     */
    public function testInvalidDsn(array $data, string $expectedMessage): void
    {
        // request config edit page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        // set form data
        $form = $crawler->selectButton('config[buttons][save]')->form();
        $form->setValues($data + [
            'config[leadconfig][contact_columns]' => ['name', 'email', 'id'], // required
        ]);

        // check if there is the given validation error
        $crawler = $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString($expectedMessage, $crawler->text());
    }

    /**
     * @return array<string, mixed[]>
     */
    public function dataInvalidDsn(): iterable
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
        include self::getContainer()->get('kernel')->getLocalConfigFile();

        return $parameters;
    }

    private function escape(string $value): string
    {
        return str_replace('%', '%%', $value);
    }
}
