<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Translation;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

class TranslatorTest extends AbstractMauticTestCase
{
    public function testMissingPluralOptions(): void
    {
        /** @var Translator $translator */
        $translator = self::getContainer()->get('translator');
        $fallback   = 'en_US';
        $locale     = 'ru';

        $reflection = new \ReflectionClass($translator);
        $property   = $reflection->getProperty('translator');
        $property->setAccessible(true);
        $internalTranslator = $property->getValue($translator);

        $internalTranslator->addLoader('array', new ArrayLoader());

        $internalTranslator->addResource('array', [
            'test.valid.message'       => 'Show warning if segment hasn\'t been rebuilt for X hours',
            'test.problematic.message' => 'This segment hasn\'t been rebuilt for 1 hour.|This segment hasn\'t been rebuilt for %count% hours.',
        ], $fallback);

        $internalTranslator->addResource('array', [
            'test.valid.message'       => 'Показывать предупреждение, если сегмент не перестраивался в течение X часов',
            'test.problematic.message' => 'Этот сегмент не перестраивался в течение 1 часа.|Этот сегмент не перестраивался в течение %count% часов.',
        ], $locale);

        $this->assertSame(
            'Показывать предупреждение, если сегмент не перестраивался в течение X часов',
            $translator->trans('test.valid.message', [], null, $locale),
            'Russian simple message expected.'
        );
        $this->assertSame(
            'Этот сегмент не перестраивался в течение 2 часов.',
            $translator->trans('test.problematic.message', ['%count%' => 2], null, $locale),
            'Russian plural message expected.'
        );
        $this->assertSame(
            'This segment hasn\'t been rebuilt for 5 hours.',
            $translator->trans('test.problematic.message', ['%count%' => 5], null, $locale),
            'Fallback to english plural message expected.'
        );
    }
}
