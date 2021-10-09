<?php

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Update\Step\UpdateTranslationsStep;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UpdateTranslationsStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|LanguageHelper
     */
    private $languageHelper;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var UpdateTranslationsStep
     */
    private $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator     = $this->createMock(TranslatorInterface::class);
        $this->languageHelper = $this->createMock(LanguageHelper::class);
        $this->logger         = $this->createMock(LoggerInterface::class);

        $this->step = new UpdateTranslationsStep($this->translator, $this->languageHelper, $this->logger);
    }

    public function testLanguageUnpackingSkippedIfJustOneLanguageIsEnabled()
    {
        $this->languageHelper->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(['en_US' => []]);

        $this->languageHelper->expects($this->never())
            ->method('fetchLanguages');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testFetchingLanguagesLogError()
    {
        $this->languageHelper->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(
                [
                    'en_US' => 'English - US',
                    'es_MX' => 'Spanish - Mexico',
                ]
            );

        $this->languageHelper->expects($this->once())
            ->method('fetchLanguages')
            ->willReturn(['error' => 'there was an error']);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('UPDATE ERROR: there was an error');

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testUsPackageSkipped()
    {
        $this->languageHelper->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(
                [
                    'en_US' => 'English - US',
                    'es_MX' => 'Spanish - Mexico',
                ]
            );

        $this->languageHelper->expects($this->once())
            ->method('fetchLanguages')
            ->willReturn([]);

        $this->languageHelper->expects($this->once())
            ->method('extractLanguagePackage')
            ->with('es_MX')
            ->willReturn(['error' => false]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testExtractionErrorIsLogged()
    {
        $this->languageHelper->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn(
                [
                    'en_US' => 'English - US',
                    'es_MX' => 'Spanish - Mexico',
                ]
            );

        $this->languageHelper->expects($this->once())
            ->method('fetchLanguages')
            ->willReturn([]);

        $this->languageHelper->expects($this->once())
            ->method('extractLanguagePackage')
            ->with('es_MX')
            ->willReturn(['error' => true]);

        $this->translator->method('trans')
            ->willReturnCallback(
                function (string $key) {
                    return $key;
                }
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('UPDATE ERROR: mautic.core.update.error_updating_language');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }
}
