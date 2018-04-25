<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\MomentumAdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\MomentumSwiftMessageValidationException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\MomentumSwiftMessageService;

/**
 * Class MomentumApiFacade.
 */
final class MomentumFacade implements MomentumFacadeInterface
{
    /**
     * @var MomentumAdapterInterface
     */
    private $momentumAdapter;

    /**
     * @var MomentumSwiftMessageService
     */
    private $momentumSwiftMessageService;

    /**
     * MomentumFacade constructor.
     *
     * @param MomentumAdapterInterface    $momentumAdapter
     * @param MomentumSwiftMessageService $momentumSwiftMessageService
     */
    public function __construct(
        MomentumAdapterInterface $momentumAdapter,
        MomentumSwiftMessageService $momentumSwiftMessageService
    ) {
        $this->momentumAdapter             = $momentumAdapter;
        $this->momentumSwiftMessageService = $momentumSwiftMessageService;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws MomentumSwiftMessageValidationException
     */
    public function send(\Swift_Mime_Message $message)
    {
        try {
            $this->momentumSwiftMessageService->validate($message);
            $momentumMessage = $this->momentumSwiftMessageService->getMomentumMessage($message);

            $response = $this->momentumAdapter->send($momentumMessage);
            $response = $response->wait();
            if (200 == (int) $response->getStatusCode()) {
                $results = $response->getBody();
                if (!$sendCount = $results['results']['total_accepted_recipients']) {
                    $this->processResponseErrors($momentumMessage, $results);
                }
            }
        } catch (\Exception $exception) {
        }
    }

    /**
     * @param array $momentumMessage
     * @param array $results
     */
    private function processResponseErrors(array $momentumMessage, array $results)
    {
        if (!empty($response['errors'][0]['code']) && 1902 == (int) $response['errors'][0]['code']) {
            $comments     = $response['errors'][0]['description'];
            $emailAddress = $momentumMessage['recipients']['to'][0]['email'];
            $metadata     = $this->getMetadata();

            if (isset($metadata[$emailAddress]) && isset($metadata[$emailAddress]['leadId'])) {
                $emailId = (!empty($metadata[$emailAddress]['emailId'])) ? $metadata[$emailAddress]['emailId'] : null;
                $this->transportCallback->addFailureByContactId($metadata[$emailAddress]['leadId'], $comments, DoNotContact::BOUNCED, $emailId);
            }
        }
    }
}
