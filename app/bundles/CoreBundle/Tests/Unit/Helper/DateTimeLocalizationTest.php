<?php

namespace Mautic\CoreBundle\Tests\Helper\DateTime;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTime\DateTimeLocalization;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeLocalizationTest extends TestCase
{
    private TranslatorInterface $translator;
    private CoreParametersHelper $coreParametersHelper;
    private DateTimeLocalization $dateTimeLocalization;

    protected function setUp(): void
    {
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->dateTimeLocalization = new DateTimeLocalization($this->translator, $this->coreParametersHelper);
    }

    /**
     * @dataProvider localizationDataProvider
     */
    public function testLocalizeWithContactLocale(string $contactLocale, string $date, string $expectedLocalizedDate): void
    {
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('locale')
            ->willReturn('en');

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function (string $key, array $params, $domain, string $locale) {
                $translations = [
                    'en' => [
                        'mautic.core.date.january' => 'Jan',
                        // Add English month and day translations here
                    ],
                    'es' => [
                        'mautic.core.date.january' => 'Ene',
                        // Add Spanish month and day translations here
                    ],
                    // Add other locales here
                ];

                return $translations[$locale][$key] ?? $key;
            });

        $result = $this->dateTimeLocalization->localize($date, $contactLocale);
        $this->assertSame($expectedLocalizedDate, $result);
    }

    public function localizationDataProvider(): array
    {
        return [
            ['en', 'January 1, 2023', 'Jan 1, 2023'],
            ['es', 'January 1, 2023', 'Ene 1, 2023'],
        ];
    }
}
