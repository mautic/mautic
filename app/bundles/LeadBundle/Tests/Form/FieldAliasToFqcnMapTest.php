<?php

namespace Mautic\LeadBundle\Tests\Form;

use Mautic\CoreBundle\Form\Type\BooleanType;
use Mautic\CoreBundle\Form\Type\CountryType;
use Mautic\CoreBundle\Form\Type\LocaleType;
use Mautic\CoreBundle\Form\Type\LookupType;
use Mautic\CoreBundle\Form\Type\MultiselectType;
use Mautic\CoreBundle\Form\Type\RegionType;
use Mautic\CoreBundle\Form\Type\SelectType;
use Mautic\CoreBundle\Form\Type\TelType;
use Mautic\CoreBundle\Form\Type\TimezoneType;
use Mautic\LeadBundle\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Form\FieldAliasToFqcnMap;
use Mautic\LeadBundle\Form\Type\HtmlType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

final class FieldAliasToFqcnMapTest extends TestCase
{
    /**
     * @dataProvider aliasFqcnProvider
     */
    public function testGetFqcn(string $alias, string $fcqn): void
    {
        $this->assertSame(FieldAliasToFqcnMap::getFqcn($alias), $fcqn);
    }

    public function testGetFqcnInvalid(): void
    {
        $alias = 'invalid_type';
        $this->expectException(FieldNotFoundException::class);
        $this->expectExceptionMessage("Field with alias {$alias} not found");
        FieldAliasToFqcnMap::getFqcn($alias);
    }

    /**
     * @return mixed[]
     */
    public function aliasFqcnProvider(): array
    {
        return [
            ['boolean', BooleanType::class],
            ['country', CountryType::class],
            ['date', DateType::class],
            ['datetime', DateTimeType::class],
            ['email', EmailType::class],
            ['hidden', HiddenType::class],
            ['locale', LocaleType::class],
            ['lookup', LookupType::class],
            ['multiselect', MultiselectType::class],
            ['number', NumberType::class],
            ['region', RegionType::class],
            ['select', SelectType::class],
            ['tel', TelType::class],
            ['text', TextType::class],
            ['textarea', TextareaType::class],
            ['time', TimeType::class],
            ['timezone', TimezoneType::class],
            ['url', UrlType::class],
            ['html', HtmlType::class],
        ];
    }
}
