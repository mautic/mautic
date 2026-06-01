<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class EmailListTypeFunctionalTest extends MauticMysqlTestCase
{
    private const PARENT_EMAIL      = 'Parent Email';
    private const VARIANT_EMAIL     = 'Variant Email';
    private const TRANSLATION_EMAIL = 'Translation Email';

    /**
     * @param bool|string|string[] $topLevel
     * @param string[]             $expectedNames
     */
    #[DataProvider('topLevelFilterDataProvider')]
    public function testGetLookupChoiceListWithTopLevelFilter(array|string|bool $topLevel, array $expectedNames): void
    {
        $parentEmail = $this->createEmail(self::PARENT_EMAIL);
        $this->createEmail(self::VARIANT_EMAIL, $parentEmail);
        $this->createEmail(self::TRANSLATION_EMAIL, null, $parentEmail);

        $this->em->clear();

        $payload = [
            'action'     => 'email:getLookupChoiceList',
            'email_type' => 'template',
            'top_level'  => $topLevel,
            'searchKey'  => 'email',
            'email'      => 'email',
        ];

        $this->assertAjaxResponseContains($payload, $expectedNames);
    }

    /**
     * @return iterable<string, array<int, bool|string|string[]>>
     */
    public static function topLevelFilterDataProvider(): iterable
    {
        yield 'array with variant and translation returns only parent' => [
            ['variant', 'translation'],
            [self::PARENT_EMAIL],
        ];

        yield 'array with variant returns parent and translation' => [
            ['variant'],
            [self::PARENT_EMAIL, self::TRANSLATION_EMAIL],
        ];

        yield 'array with translation returns parents and variants' => [
            ['translation'],
            [self::PARENT_EMAIL, self::VARIANT_EMAIL],
        ];

        yield 'true returns only parent' => [
            true,
            [self::PARENT_EMAIL],
        ];

        yield 'string variant returns parent and translation' => [
            'variant',
            [self::PARENT_EMAIL, self::TRANSLATION_EMAIL],
        ];

        yield 'string translation returns parents and variants' => [
            'translation',
            [self::PARENT_EMAIL, self::VARIANT_EMAIL],
        ];

        yield 'unknown filter returns all' => [
            ['unknown_filter'],
            [self::PARENT_EMAIL, self::VARIANT_EMAIL, self::TRANSLATION_EMAIL],
        ];

        yield 'false returns all' => [
            false,
            [self::PARENT_EMAIL, self::VARIANT_EMAIL, self::TRANSLATION_EMAIL],
        ];

        yield 'empty array returns all' => [
            [],
            [self::PARENT_EMAIL, self::VARIANT_EMAIL, self::TRANSLATION_EMAIL],
        ];
    }

    private function createEmail(string $name, ?Email $variantParent = null, ?Email $translationParent = null): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('template');
        if ($variantParent) {
            $email->setVariantParent($variantParent);
        }
        if ($translationParent) {
            $email->setTranslationParent($translationParent);
        }
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @param array<string, array<string>|bool|string> $payload
     * @param string[]                                 $expectedNames
     */
    private function assertAjaxResponseContains(array $payload, array $expectedNames): void
    {
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax', $payload);
        $this->assertResponseIsSuccessful();

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $items = $response[0]['items'] ?? [];

        $actualNames = array_values($items);
        sort($actualNames);
        sort($expectedNames);

        $this->assertSame($expectedNames, $actualNames);
    }
}
