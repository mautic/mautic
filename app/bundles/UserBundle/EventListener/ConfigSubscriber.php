<?php

/*
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\UserBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfigSubscriber implements EventSubscriberInterface
{
    private $fileFields = ['saml_idp_metadata', 'saml_idp_own_certificate', 'saml_idp_own_private_key'];

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addFileFields($this->fileFields)
            ->addForm(
            [
                'bundle'     => 'UserBundle',
                'formAlias'  => 'userconfig',
                'formType'   => ConfigType::class,
                'formTheme'  => 'MauticUserBundle:FormTheme\Config',
                'parameters' => $event->getParametersFromConfig('MauticUserBundle'),
            ]
        );
    }

    public function onConfigSave(ConfigEvent $event)
    {
        // Preserve existing value
        $event->unsetIfEmpty('saml_idp_own_password');

        $data = $event->getConfig('userconfig');

        foreach ($this->fileFields as $field) {
            if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                $data[$field] = $event->getFileContent($data[$field]);

                switch ($field) {
                    case 'saml_idp_metadata':
                        if (!$this->validateXml($data[$field])) {
                            $event->setError('mautic.user.saml.metadata.invalid', [], 'userconfig', $field);
                        }
                        break;
                    case 'saml_idp_own_certificate':
                        if (0 !== strpos($data[$field], '-----BEGIN CERTIFICATE-----')) {
                            $event->setError('mautic.user.saml.certificate.invalid', [], 'userconfig', $field);
                        }
                        break;
                    case 'saml_idp_own_private_key':
                        if (0 !== strpos($data[$field], '-----BEGIN RSA PRIVATE KEY-----')) {
                            $event->setError('mautic.user.saml.private_key.invalid', [], 'userconfig', $field);
                        }
                        break;
                }

                $data[$field] = $event->encodeFileContents($data[$field]);
            }
        }

        $event->setConfig($data, 'userconfig');
    }

    /**
     * @param $content
     *
     * @return bool
     */
    private function validateXml($content)
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
