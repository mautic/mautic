<?php

namespace CoreBundle\Test\Templating\Helper;

use Mautic\CoreBundle\Templating\Helper\TranslatorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorHelperTest extends TestCase
{
    public function testGetJsLang()
    {
        $translator = $this->createMock(Translator::class);
        $translator->expects($this->exactly(2))->method('getCatalogue')->willReturn(
            new class() {
                public function all()
                {
                    return [];
                }
            }
        );

        $translatorHelper = new TranslatorHelper($translator);
        $translatorHelper->getJsLang();
    }

    public function testGetJsLangBasedOnLocale()
    {
        $translator = $this->createMock(Translator::class);
        $translator->method('setLocale')->willReturnCallback(
            function ($locale) use ($translator) {
                $translator->method('getLocale')->willReturn($locale);
            }
        );
        $translator->method('getCatalogue')->willReturnCallback(
            function () use ($translator) {
                return new TranslatorCatalogue($translator);
            }
        );

        $translatorHelper = new TranslatorHelper($translator);
        $jsLang           = json_decode($translatorHelper->getJsLang(), true);
        $this->assertArrayHasKey('mautic.custom.string', $jsLang);
        $this->assertEquals('en_US string', $jsLang['mautic.custom.string']);

        $translator->setLocale('fr_FR');
        $translatorHelper = new TranslatorHelper($translator);
        $jsLang           = json_decode($translatorHelper->getJsLang(), true);
        $this->assertArrayHasKey('mautic.custom.string', $jsLang);
        $this->assertEquals('fr_FR string', $jsLang['mautic.custom.string']);
    }
}

class TranslatorCatalogue
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function all()
    {
        switch ($this->translator->getLocale()) {
            case 'fr_FR':
                return ['mautic.custom.string' => 'fr_FR string'];
            case 'en_US':
            default:
                return ['mautic.custom.string' => 'en_US string'];
        }
    }
}
