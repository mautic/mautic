<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\UriFactory;
use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\Transifex\ApiFactory;
use Mautic\Transifex\Transifex;
use Mautic\Transifex\TransifexInterface;
use Psr\Http\Client\ClientInterface;

class TransifexFactory
{
    private $client;
    private $coreParametersHelper;
    private $transifex;

    public function __construct(
        ClientInterface $client,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->client               = $client;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function getTransifex(): TransifexInterface
    {
        if (!$this->transifex) {
            $username = $this->coreParametersHelper->get('transifex_username');
            $password = $this->coreParametersHelper->get('transifex_password');

            if (empty($username) || empty($password)) {
                throw new BadConfigurationException('Transifex credentials are required to connect to Transifex API. Insert transifex_username and transifex_password params to your local.php file.');
            }

            $this->transifex = $this->create($this->client, $username, $password);
        }

        return $this->transifex;
    }

    private function create(ClientInterface $client, string $username, string $password): TransifexInterface
    {
        $apiFactory = new ApiFactory(
            $client,
            new RequestFactory(),
            new StreamFactory(),
            new UriFactory()
        );

        $options = [
            'api.username' => $username,
            'api.password' => $password,
        ];

        return new Transifex($apiFactory, $options);
    }
}
