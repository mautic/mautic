<?php

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateTranslationsStep implements StepInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private LanguageHelper $languageHelper,
        private LoggerInterface $logger
    ) {
    }

    public function getOrder(): int
    {
        return 40;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return true;
    }

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        // Update languages
        $supportedLanguages = $this->languageHelper->getSupportedLanguages();

        // If there is only one language, assume it is 'en_US' and skip this
        if (count($supportedLanguages) <= 1) {
            return;
        }

        $progressBar->setMessage($this->translator->trans('mautic.core.command.update.step.update_languages'));
        $progressBar->advance();

        // First, update the cached language data
        $result = $this->languageHelper->fetchLanguages(true);

        // Only continue if not in error
        if (isset($result['error'])) {
            $this->logger->error('UPDATE ERROR: '.$result['error']);

            return;
        }

        foreach ($supportedLanguages as $locale => $name) {
            $this->updateLanguage($locale, $name);
        }
    }

    private function updateLanguage(string $locale, string $name): void
    {
        // We don't need to update en_US, that comes with the main package
        if ('en_US' === $locale) {
            return;
        }

        // Update time
        $extractResult = $this->languageHelper->extractLanguagePackage($locale);
        if (empty($extractResult['error'])) {
            return;
        }

        $this->logger->error(
            'UPDATE ERROR: '.
            $this->translator->trans(
                'mautic.core.update.error_updating_language',
                [
                    '%language%' => $name,
                ]
            )
        );
    }
}
