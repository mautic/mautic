<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Facade\MomentumSendException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;
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

    /** @var Logger */
    private $logger;

    /**
     * MomentumFacade constructor.
     *
     * @param AdapterInterface               $adapter
     * @param SwiftMessageServiceInterface   $swiftMessageService
     * @param SwiftMessageValidatorInterface $swiftMessageValidator
     * @param Logger                         $logger
     */
    public function __construct(
        AdapterInterface $adapter,
        SwiftMessageServiceInterface $swiftMessageService,
        SwiftMessageValidatorInterface $swiftMessageValidator,
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
     * @throws SwiftMessageValidationException
     * @throws MomentumSendException
     */
    public function send(\Swift_Mime_Message $message)
    {
        try {
            $this->swiftMessageValidator->validate($message);
            $transmission = $this->swiftMessageService->transformToTransmission($message);
            $response     = $this->adapter->createTransmission($transmission);
            $response     = $response->wait();
            if (200 !== (int) $response->getStatusCode()) {
                $this->logger->addError(
                    'Momentum send: '.$response->getStatusCode(),
                    [
                        'response' => $response->getBody(),
                    ]
                );
            }
        } catch (\Exception $exception) {
            $this->logger->addError(
                'Momentum send exception',
                [
                    'message' => $exception->getMessage(),
                ]);
            if ($exception instanceof SwiftMessageValidationException) {
                throw $exception;
            }
            throw new MomentumSendException();
        }
    }
}
