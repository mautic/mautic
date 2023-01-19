<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Factory;

use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\UriFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\Transifex\Config;
use Mautic\Transifex\Exception\InvalidConfigurationException;
use Mautic\Transifex\Transifex;
use Mautic\Transifex\TransifexInterface;
use Psr\Http\Client\ClientInterface;

class TransifexFactory
{
    private ClientInterface $client;
    private CoreParametersHelper $coreParametersHelper;
    private ?TransifexInterface $transifex = null;

    public function __construct(
        ClientInterface $client,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->client               = $client;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getTransifex(): TransifexInterface
    {
        if (!$this->transifex) {
            $this->transifex = $this->create($this->client, $this->coreParametersHelper->get('transifex_api_token') ?? '');
        }

        return $this->transifex;
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function create(ClientInterface $client, string $apiToken): TransifexInterface
    {
        $config = new Config();
        $config->setApiToken($apiToken);
        $config->setOrganization('mautic');
        $config->setProject('mautic');

        return new Transifex($client, new RequestFactory(), new StreamFactory(), new UriFactory(), $config);
    }
}
