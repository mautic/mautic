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
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
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

    /**
     * @param ConfigBuilderEvent $event
     */
    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addFileFields($this->fileFields)
              ->addForm(
                  [
                      'bundle'     => 'UserBundle',
                      'formAlias'  => 'userconfig',
                      'formTheme'  => 'MauticUserBundle:FormTheme\Config',
                      'parameters' => $event->getParametersFromConfig('MauticUserBundle'),
                  ]
              );
    }

    /**
     * @param ConfigEvent $event
     */
    public function onConfigSave(ConfigEvent $event)
    {
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
                        if (strpos($data[$field], '-----BEGIN CERTIFICATE-----') !== 0) {
                            $event->setError('mautic.user.saml.certificate.invalid', [], 'userconfig', $field);
                        }
                        break;
                    case 'saml_idp_own_private_key':
                        if (strpos($data[$field], '-----BEGIN RSA PRIVATE KEY-----') !== 0) {
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
    protected function validateXml($content)
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
