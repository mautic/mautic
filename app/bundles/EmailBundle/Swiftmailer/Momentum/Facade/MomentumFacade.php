<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallback;
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
     * @var MomentumCallback
     */
    private $momentumCallback;

    /**
     * MomentumFacade constructor.
     *
     * @param AdapterInterface               $adapter
     * @param SwiftMessageServiceInterface   $swiftMessageService
     * @param SwiftMessageValidatorInterface $swiftMessageValidator
     * @param MomentumCallback               $momentumCallback
     * @param Logger                         $logger
     */
    public function __construct(
        AdapterInterface $adapter,
        SwiftMessageServiceInterface $swiftMessageService,
        SwiftMessageValidatorInterface $swiftMessageValidator,
        MomentumCallback $momentumCallback,
        Logger $logger
    ) {
        $this->adapter               = $adapter;
        $this->swiftMessageService   = $swiftMessageService;
        $this->swiftMessageValidator = $swiftMessageValidator;
        $this->logger                = $logger;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_Message $message)
    {
        try {
            $this->swiftMessageValidator->validate($message);
            $transmission = $this->swiftMessageService->transformToTransmission($message);
            $response     = $this->adapter->createTransmission($transmission);
            $response     = $response->wait();

            if (200 === (int) $response->getStatusCode()) {
                $results = $response->getBody();
                if (!$sendCount = $results['results']['total_accepted_recipients']) {
                    $this->momentumCallback->processImmediateFeedback(key($message->getTo()), $results);
                }

                return $sendCount;
            }

            $message = $this->getErrors($response->getBody());

            $this->logger->addError(
                'Momentum send: '.$response->getStatusCode(),
                [
                    'response' => $response->getBody(),
                ]
            );

            throw new MomentumSendException($message);
        } catch (\Exception $exception) {
            $this->logger->addError(
                'Momentum send exception',
                [
                    'message' => $exception->getMessage(),
                ]);

            throw $exception;
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
                $errors[] = $error['message'].' : '.$error['description'];
            }

            return implode('; ', $errors);
        }

        return var_export($body, true);
    }
}
