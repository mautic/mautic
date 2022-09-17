<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Aws\Credentials\Credentials;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use Mautic\EmailBundle\Mailer\Callback\AmazonCallback;
use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport\Dsn;

class SesApiTransportExtension implements CallbackTransportInterface, TransportExtensionInterface, TestConnectionInterface
{
    private AmazonCallback $amazonCallback;

    public function __construct(AmazonCallback $amazonCallback)
    {
        $this->amazonCallback = $amazonCallback;
    }

    public function getSupportedSchemes(): array
    {
        return ['ses+api', 'amazon_api'];
    }

    public function processCallbackRequest(Request $request): void
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    public function testConnection(Dsn $dsn): bool
    {
        $client = $this->createAmazonClient($dsn);

        try {
            $account             = $client->getAccount();
            $emailQuotaRemaining = $account->get('SendQuota')['Max24HourSend'] - $account->get('SendQuota')['SentLast24Hours'];
        } catch (SesV2Exception $exception) {
            throw new ConnectionErrorException($exception->getMessage());
        }

        if (!$account->get('SendingEnabled')) {
            throw new ConnectionErrorException('Your AWS SES is not enabled for sending');
        }

        if ($emailQuotaRemaining <= 0) {
            throw new ConnectionErrorException('Your AWS SES quota is currently exceeded');
        }

        return true;
    }

    protected function createAmazonClient(Dsn $dsn): SesV2Client
    {
        $config  = [
            'version'     => '2019-09-27',
            'region'      => $dsn->getOption('region', 'us-east-1'),
            'credentials' => new Credentials(
                $dsn->getUser(),
                $dsn->getPassword()
            ),
        ];

        return new SesV2Client($config);
    }
}
