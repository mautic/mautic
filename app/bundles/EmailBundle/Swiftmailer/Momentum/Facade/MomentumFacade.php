<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Facade\MomentumSendException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageServiceInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator\SwiftMessageValidatorInterface;

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
     * MomentumFacade constructor.
     *
     * @param AdapterInterface               $adapter
     * @param SwiftMessageServiceInterface   $swiftMessageService
     * @param SwiftMessageValidatorInterface $swiftMessageValidator
     */
    public function __construct(
        AdapterInterface $adapter,
        SwiftMessageServiceInterface $swiftMessageService,
        SwiftMessageValidatorInterface $swiftMessageValidator
    ) {
        $this->adapter               = $adapter;
        $this->swiftMessageService   = $swiftMessageService;
        $this->swiftMessageValidator = $swiftMessageValidator;
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
            if (200 == (int) $response->getStatusCode()) {
                $results = $response->getBody();
                if (!$sendCount = $results['results']['total_accepted_recipients']) {
                    $this->processResponseErrors($transmission, $results);
                }
            }
        } catch (\Exception $exception) {
            dump($exception);
            exit;
            if ($exception instanceof SwiftMessageValidationException) {
                throw $exception;
            }
            throw new MomentumSendException();
        }
    }

    /**
     * @param TransmissionDTO $transmissionDTO
     * @param array           $results
     */
    private function processResponseErrors(TransmissionDTO $transmissionDTO, array $results)
    {
        /*
        if (!empty($response['errors'][0]['code']) && 1902 == (int) $response['errors'][0]['code']) {
            $comments     = $response['errors'][0]['description'];
            $emailAddress = $momentumMessage['recipients']['to'][0]['email'];
            $metadata     = $this->getMetadata();

            if (isset($metadata[$emailAddress]) && isset($metadata[$emailAddress]['leadId'])) {
                $emailId = (!empty($metadata[$emailAddress]['emailId'])) ? $metadata[$emailAddress]['emailId'] : null;
                $this->transportCallback->addFailureByContactId($metadata[$emailAddress]['leadId'], $comments, DoNotContact::BOUNCED, $emailId);
            }
        }*/
    }
}
