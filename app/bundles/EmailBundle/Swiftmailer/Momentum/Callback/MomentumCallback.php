<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

final class MomentumCallback implements MomentumCallbackInterface
{
    /**
     * @var TransportCallback
     */
    private $transportCallback;

    /**
     * MomentumCallback constructor.
     *
     * @param TransportCallback $transportCallback
     */
    public function __construct(TransportCallback $transportCallback)
    {
        $this->transportCallback = $transportCallback;
    }

    /**
     * @param Request $request
     */
    public function processCallbackRequest(Request $request)
    {
        $responseItems = new ResponseItems($request);
        foreach ($responseItems as $item) {
            $this->transportCallback->addFailureByAddress($item->getEmail(), $item->getReason(), $item->getDncReason());
        }
    }

    /**
     * @param $emailAddress
     * @param $response
     */
    public function processImmediateFeedback($emailAddress, array $response)
    {
        if (!empty($response['errors'][0]['code']) && 1902 == (int) $response['errors'][0]['code']) {
            $comments     = $response['errors'][0]['description'];
            $metadata     = $this->getMetadata();

            if (isset($metadata[$emailAddress]) && isset($metadata[$emailAddress]['leadId'])) {
                $emailId = (!empty($metadata[$emailAddress]['emailId'])) ? $metadata[$emailAddress]['emailId'] : null;
                $this->transportCallback->addFailureByContactId($metadata[$emailAddress]['leadId'], $comments, DoNotContact::BOUNCED, $emailId);
            }
        }
    }
}
