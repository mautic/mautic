<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\Integration;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

/**
 * Class AmazonS3Integration.
 */
class AmazonS3Integration extends CloudStorageIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AmazonS3';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'Amazon S3';
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret',
            'bucket'        => 'mautic.integration.keyfield.amazons3.bucket',
        ];
    }

    /**
     * @param Form|FormBuilder $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('keys' === $formArea) {
            $builder->add(
                'region',
                TextType::class,
                [
                    'label'       => 'mautic.integration.Amazon.region',
                    'required'    => false,
                    'attr'        => [
                        'class'   => 'form-control',
                    ],
                    'data'        => empty($data['region']) ? 'us-east-1' : $data['region'],
                    'required'    => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return AwsS3
     */
    public function getAdapter()
    {
        if (!$this->adapter) {
            $keys = $this->getDecryptedApiKeys();

            $service = new S3Client(
                [
                    'version'     => 'latest',
                    'region'      => (empty($keys['region'])) ? 'us-east-1' : $keys['region'],
                    'credentials' => [
                        'key'    => $keys['client_id'],
                        'secret' => $keys['client_secret'],
                    ],
                ]
            );

            $this->adapter = new AwsS3($service, $keys['bucket']);
        }

        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicUrl($key)
    {
        return $this->getAdapter()->getUrl($key);
    }
}
