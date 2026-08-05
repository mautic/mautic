<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Integration;

use Aws\S3\S3Client;
use Gaufrette\Adapter;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;
use Gaufrette\Filesystem;
use MauticPlugin\MauticCloudStorageBundle\Exception\InvalidCredentialConfigurationException;

final class AmazonS3Integration extends CloudStorageIntegration
{
    private ?ResolvableFilesystem $fileSystem = null;

    public function getName(): string
    {
        return 'AmazonS3';
    }

    public function getDisplayName(): string
    {
        return 'Amazon S3';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticCloudStorageBundle/Assets/img/amazons3.png';
    }

    public function getAdapter(): Adapter
    {
        if (!$this->adapter || !$this->fileSystem) {
            $keys = $this->getDecryptedApiKeys();
            if (empty($keys['client_id']) || empty($keys['client_secret'])) {
                throw new InvalidCredentialConfigurationException('AmazonS3Integration misconfigured: client_id or client_secret missing.');
            }

            $s3Args = [
                'version'     => 'latest',
                'region'      => (empty($keys['region'])) ? 'us-east-1' : $keys['region'],
                'credentials' => [
                    'key'    => $keys['client_id'],
                    'secret' => $keys['client_secret'],
                ],
            ];
            if (!empty($keys['endpoint'])) {
                $s3Args['endpoint'] = $keys['endpoint'];
            }

            $service = new S3Client($s3Args);

            $this->adapter    = new AwsS3($service, $keys['bucket']);
            $decorated        = new Filesystem($this->adapter);
            $this->fileSystem = new ResolvableFilesystem(
                $decorated,
                new AwsS3PublicUrlResolver($service, $keys['bucket'])
            );
        }

        return $this->adapter;
    }

    public function getPublicUrl($key)
    {
        $this->getAdapter();

        return $this->fileSystem->resolve($key);
    }
}
