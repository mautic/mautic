<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigPreSaveEvent;
use Mautic\CoreBundle\Ai\ModelWorkload;
use Mautic\CoreBundle\Ai\OpenAiCompatibleProvider;
use Mautic\CoreBundle\Ai\ProviderLocation;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Form\Type\ConfigAiType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ConfigAiSubscriber
{
    public function __construct(
        private Configurator $configurator,
        private CoreParametersHelper $parametersHelper,
        private EncryptionHelper $encryptionHelper,
    ) {
    }

    #[AsEventListener]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $parameters = $event->getParametersFromConfig('MauticCoreBundle');
        $fields     = [ConfigAiType::PROVIDERS, ConfigAiType::ALLOW_CLOUD];
        foreach (ModelWorkload::cases() as $workload) {
            $fields[] = 'ai_'.$workload->value.'_provider';
            $fields[] = 'ai_'.$workload->value.'_model';
        }

        $event->addForm([
            'bundle'     => 'CoreBundle',
            'formAlias'  => ConfigAiType::ALIAS,
            'formType'   => ConfigAiType::class,
            'formTheme'  => '@MauticCore/FormTheme/Config/_config_aiconfig_widget.html.twig',
            'parameters' => array_intersect_key($parameters, array_flip($fields)),
        ]);
    }

    #[AsEventListener]
    public function onConfigPreSave(ConfigPreSaveEvent $event): void
    {
        $config = $event->getConfig(ConfigAiType::ALIAS);
        if (!isset($config[ConfigAiType::PROVIDERS])) {
            return;
        }

        foreach (ModelWorkload::cases() as $workload) {
            $prefix   = 'ai_'.$workload->value;
            $provider = $config[$prefix.'_provider'] ?? null;
            $model    = $config[$prefix.'_model'] ?? null;
            if ((null === $provider) !== (null === $model)) {
                $event->setError('mautic.core.ai.error.assignment', [], ConfigAiType::ALIAS, $prefix.(null === $provider ? '_provider' : '_model'));
            }
        }

        $previous = $this->configurator->getParameters()[ConfigAiType::PROVIDERS] ?? [];
        foreach ($config[ConfigAiType::PROVIDERS] as $id => &$row) {
            if (!is_int($id) || $id < 0 || $id > 1_000_000) {
                $event->setError('mautic.core.ai.error.provider_id', [], ConfigAiType::ALIAS, ConfigAiType::PROVIDERS);

                return;
            }

            $stored = $previous[$id] ?? [];
            $carryKey = $row['endpoint'] === ($stored['endpoint'] ?? null) && $row['location'] === ($stored['location'] ?? null);
            $newKey   = $row['api_key'];
            $savedKey = !$row['remove_key'] && $carryKey && '' !== ($stored['api_key'] ?? '') ? $stored['api_key'] : null;
            if ($row['remove_key'] && null !== $newKey) {
                $event->setError('mautic.core.ai.error.remove_or_replace', [], ConfigAiType::ALIAS, ConfigAiType::PROVIDERS);

                return;
            }

            try {
                OpenAiCompatibleProvider::validateEndpoint(
                    str_replace('%%', '%', $row['endpoint']),
                    ProviderLocation::from($row['location']),
                    null !== $newKey || null !== $savedKey,
                );
            } catch (\InvalidArgumentException) {
                $event->setError('mautic.core.ai.error.endpoint', ['%name%' => str_replace('%%', '%', $row['name'])], ConfigAiType::ALIAS, ConfigAiType::PROVIDERS);

                return;
            }

            if (null !== $newKey && in_array($this->parametersHelper->get('secret_key'), [null, '', 'temp'], true)) {
                $event->setError('mautic.core.ai.error.secret', [], ConfigAiType::ALIAS, ConfigAiType::PROVIDERS);

                return;
            }

            $row['api_key'] = null === $newKey ? $savedKey : $this->encryptionHelper->encrypt($newKey);
            unset($row['remove_key']);
        }
        unset($row);

        $event->setConfig($config, ConfigAiType::ALIAS);
    }
}
