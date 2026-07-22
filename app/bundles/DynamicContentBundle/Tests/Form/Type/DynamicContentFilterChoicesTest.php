<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

/**
 * Drives the real DWC form (DynamicContentType) end-to-end through the
 * controller with locale, timezone and region filter values to prove the form
 * actually accepts those choices and persists them.
 */
final class DynamicContentFilterChoicesTest extends MauticMysqlTestCase
{
    private const LOCALE_VALUE   = 'en_US';

    private const TIMEZONE_VALUE = 'America/New_York';

    private const REGION_VALUE   = 'California';

    private const REGION_INDEX_NUMBER = '4';

    public function testLocaleFilterValueIsAcceptedByTheForm(): void
    {
        $entity  = $this->submitDwcForm('DWC locale filter', [
            $this->buildFilter('preferred_locale', 'locale', self::LOCALE_VALUE),
        ]);
        $filters = array_values($entity->getFilters());

        Assert::assertCount(1, $filters);
        Assert::assertSame('preferred_locale', $filters[0]['field']);
        Assert::assertSame('locale', $filters[0]['type']);
        Assert::assertSame(self::LOCALE_VALUE, $filters[0]['filter']);
    }

    public function testTimezoneFilterValueIsAcceptedByTheForm(): void
    {
        $entity  = $this->submitDwcForm('DWC timezone filter', [
            $this->buildFilter('timezone', 'timezone', self::TIMEZONE_VALUE),
        ]);
        $filters = array_values($entity->getFilters());

        Assert::assertCount(1, $filters);
        Assert::assertSame('timezone', $filters[0]['field']);
        Assert::assertSame('timezone', $filters[0]['type']);
        Assert::assertSame(self::TIMEZONE_VALUE, $filters[0]['filter']);
    }

    public function testTimezoneLocaleAndRegionFiltersAreSavedTogether(): void
    {
        $entity  = $this->submitDwcForm('DWC locale timezone region filters', [
            $this->buildFilter('timezone', 'timezone', self::TIMEZONE_VALUE),
            $this->buildFilter('preferred_locale', 'locale', self::LOCALE_VALUE),
            $this->buildFilter('state', 'region', self::REGION_INDEX_NUMBER),
        ]);
        $filters = array_values($entity->getFilters());

        Assert::assertCount(3, $filters);
        Assert::assertSame(self::TIMEZONE_VALUE, $filters[0]['filter']);
        Assert::assertSame(self::LOCALE_VALUE, $filters[1]['filter']);
        Assert::assertSame(self::REGION_VALUE, $filters[2]['filter']);
    }

    public function testInvalidLocaleFilterValueIsRejectedByTheForm(): void
    {
        $name    = 'DWC invalid locale filter';
        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/new');
        self::assertResponseIsSuccessful();

        $form   = $crawler->selectButton('Save')->form();
        $values = $form->getPhpValues();

        $values['dwc']['name']    = $name;
        $values['dwc']['type']    = 'html';
        $values['dwc']['content'] = 'Some content';
        $values['dwc']['filters'] = [
            $this->buildFilter('preferred_locale', 'locale', 'not-a-real-locale'),
        ];

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $entity = $this->em->getRepository(DynamicContent::class)->findOneBy(['name' => $name]);
        Assert::assertNull($entity, 'A DWC with an invalid locale filter value must not be saved.');
    }

    /**
     * Submits the real DWC form with the given filters and returns the
     * saved entity.
     *
     * @param array<int, array<string, string>> $filters
     */
    private function submitDwcForm(string $name, array $filters): DynamicContent
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/dwc/new');
        self::assertResponseIsSuccessful();

        $form   = $crawler->selectButton('Save')->form();
        $values = $form->getPhpValues();

        $values['dwc']['name']    = $name;
        $values['dwc']['type']    = 'html';
        $values['dwc']['content'] = 'Some content';
        $values['dwc']['filters'] = $filters;

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);
        self::assertResponseIsSuccessful();

        $this->em->clear();

        $entity = $this->em->getRepository(DynamicContent::class)->findOneBy(['name' => $name]);
        Assert::assertInstanceOf(DynamicContent::class, $entity, 'The DWC should have been saved.');

        return $entity;
    }

    /**
     * @return array<string, string>
     */
    private function buildFilter(string $field, string $type, string $value): array
    {
        return [
            'glue'     => 'and',
            'field'    => $field,
            'object'   => 'lead',
            'type'     => $type,
            'operator' => '=',
            'filter'   => $value,
            'display'  => '',
        ];
    }
}
