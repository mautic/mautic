<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var string[]
     */
    private array $fileFields = [
        'saml_idp_metadata',
        'saml_idp_own_certificate',
        'saml_idp_own_private_key',
    ];

    public function __construct(
        private readonly ClientFactoryInterface $clientFactory,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addFileFields($this->fileFields)
            ->addForm(
                [
                    'bundle'     => 'UserBundle',
                    'formAlias'  => 'userconfig',
                    'formType'   => ConfigType::class,
                    'formTheme'  => '@MauticUser/FormTheme/Config/_config_userconfig_widget.html.twig',
                    'parameters' => $event->getParametersFromConfig('MauticUserBundle'),
                ]
            );
    }

    public function onConfigSave(ConfigEvent $event): void
    {
        $this->validateOidcConnection($event);
        $this->processSamlConfig($event);
    }

    private function validateOidcConnection(ConfigEvent $event): void
    {
        if (1 !== $event->getConfig()['userconfig']['open_id_is_enabled']) {
            return;
        }

        $credentials = new ClientCredentials(
            $event->getConfig()['userconfig']['open_id_client_url'],
            $event->getConfig()['userconfig']['open_id_client_id'],
            $event->getConfig()['userconfig']['open_id_client_secret'],
            $event->getConfig()['userconfig']['open_id_mapping_field']
        );
        $client = $this->clientFactory->create($credentials);

        if ($error = $client->testConnection()) {
            $message = $this->translator->trans('mautic.open_id.config.exception.test_connection_failed');
            $this->logger->debug($message, ['error' => $error]);
            $event->setError($message, [], 'userconfig', 'open_id_is_enabled');
        }
    }

    private function processSamlConfig(ConfigEvent $event): void
    {
        // Preserve existing value
        $event->unsetIfEmpty('saml_idp_own_password');

        $data = $event->getConfig('userconfig');

        foreach ($this->fileFields as $field) {
            if (!isset($data[$field]) || !$data[$field] instanceof UploadedFile) {
                continue;
            }

            $data[$field] = $event->getFileContent($data[$field]);

            switch ($field) {
                case 'saml_idp_metadata':
                    if (!$this->validateXml($data[$field])) {
                        $event->setError('mautic.user.saml.metadata.invalid', [], 'userconfig', $field);
                    }
                    break;
                case 'saml_idp_own_certificate':
                    if (!str_starts_with($data[$field], '-----BEGIN CERTIFICATE-----')) {
                        $event->setError('mautic.user.saml.certificate.invalid', [], 'userconfig', $field);
                    }
                    break;
                case 'saml_idp_own_private_key':
                    $encryptedKey = str_starts_with($data[$field], '-----BEGIN ENCRYPTED PRIVATE KEY-----');
                    $decryptedKey = str_starts_with($data[$field], '-----BEGIN RSA PRIVATE KEY-----');

                    if (!$encryptedKey && !$decryptedKey) {
                        $event->setError('mautic.user.saml.private_key.invalid', [], 'userconfig', $field);
                    }

                    if ($encryptedKey && empty($data['saml_idp_own_password'])) {
                        $event->setError('mautic.user.saml.private_key.password_needed', [], 'userconfig', 'saml_idp_own_password');
                    }

                    if ($encryptedKey && !empty($data['saml_idp_own_password']) && !openssl_get_privatekey($data[$field], $data['saml_idp_own_password'])) {
                        $event->setError('mautic.user.saml.private_key.password_invalid', [], 'userconfig', 'saml_idp_own_password');
                    }

                    break;
            }

            $data[$field] = $event->encodeFileContents($data[$field]);
        }

        $event->setConfig($data, 'userconfig');
    }

    private function validateXml(string $content): bool
    {
        $valid = true;

        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        if (false === $doc) {
            $valid = false;
            libxml_clear_errors();
        }

        return $valid;
    }
}
