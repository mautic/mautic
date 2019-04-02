<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallbackInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Facade\MomentumSendException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageServiceInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator\SwiftMessageValidatorInterface;
use Monolog\Logger;

/**
 * Class MomentumApiFacade.
 */
final class MomentumFacade implements MomentumFacadeInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var SwiftMessageServiceInterface
     */
    private $swiftMessageService;

    /**
     * @var SwiftMessageValidatorInterface
     */
    private $swiftMessageValidator;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MomentumCallbackInterface
     */
    private $momentumCallback;

    /**
     * MomentumFacade constructor.
     *
     * @param AdapterInterface               $adapter
     * @param SwiftMessageServiceInterface   $swiftMessageService
     * @param SwiftMessageValidatorInterface $swiftMessageValidator
     * @param MomentumCallbackInterface      $momentumCallback
     * @param Logger                         $logger
     */
    public function __construct(
        AdapterInterface $adapter,
        SwiftMessageServiceInterface $swiftMessageService,
        SwiftMessageValidatorInterface $swiftMessageValidator,
        MomentumCallbackInterface $momentumCallback,
        Logger $logger
    ) {
        $this->adapter               = $adapter;
        $this->swiftMessageService   = $swiftMessageService;
        $this->swiftMessageValidator = $swiftMessageValidator;
        $this->momentumCallback      = $momentumCallback;
        $this->logger                = $logger;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return mixed
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_Message $message)
    {
        try {
            $this->swiftMessageValidator->validate($message);
            $transmission = $this->swiftMessageService->transformToTransmission($message);
            $attempt      = 0;
            do {
                if (0 !== $attempt) {
                    sleep(5);
                }
                $attempt += 1;
                $response = $this->adapter->createTransmission($transmission);
                $response = $response->wait();
            } while (500 === (int) $response->getStatusCode() && 3 > $attempt);

            if (200 === (int) $response->getStatusCode()) {
                $results = $response->getBody();
                if (!$sendCount = $results['results']['total_accepted_recipients']) {
                    $this->momentumCallback->processImmediateFeedback($message, $results);
                }

                return $sendCount;
            }

            $this->logger->addError(
                'Momentum send: '.$response->getStatusCode(),
                [
                    'response' => $response->getBody(),
                ]
            );

            throw new MomentumSendException($this->getErrors($response->getBody()));
        } catch (\Exception $exception) {
            $this->logger->addError(
                'Momentum send exception',
                [
                    'message' => $exception->getMessage(),
                ]);

            throw new MomentumSendException($exception->getMessage());
        }
    }

    /**
     * @param $body
     *
     * @return string
     */
    private function getErrors($body)
    {
        if (!is_array($body)) {
            return (string) $body;
        }

        if (isset($body['errors'])) {
            $errors = [];
            foreach ($body['errors'] as $error) {
                $error = $error['message'];

                if (isset($error['description'])) {
                    $error .= ' : '.$error['description'];
                }

                $errors[] = $error;
            }

            return implode('; ', $errors);
        }

        return var_export($body, true);
    }
}
