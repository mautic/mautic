<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MandrillTransport
 */
class MandrillTransport extends AbstractBatchHttpTransport implements InterfaceCallbackTransport
{

    /**
     * {@inheritdoc}
     */
    protected function getPayload()
    {
        $metadata     = $this->getMetadata();
        $mauticTokens = $mandrillMergeVars = $mandrillMergePlaceholders = array();

        // Mandrill uses *|PLACEHOLDER|* for tokens so Mautic's need to be replaced
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : array();
            $mauticTokens = array_keys($tokens);

            $mandrillMergeVars = $mandrillMergePlaceholders = array();
            foreach ($mauticTokens as $token) {
                $mandrillMergeVars[$token]         = strtoupper(preg_replace("/[^a-z0-9]+/i", "", $token));
                $mandrillMergePlaceholders[$token] = '*|'.$mandrillMergeVars[$token].'|*';
            }
        }

        $message = $this->messageToArray($mauticTokens, $mandrillMergePlaceholders);

        $message['from_email'] = $message['from']['email'];
        $message['from_name']  = $message['from']['name'];
        unset($message['from']);

        // Do not expose all the emails in the "to"
        $message['preserve_recipients'] = false;

        // Generate the recipients
        $recipients = $rcptMergeVars = $rcptMetadata = array();

        foreach ($message['recipients'] as $type => $typeRecipients) {
            foreach ($typeRecipients as $rcpt) {
                $rcpt['type'] = $type;
                $recipients[] = $rcpt;

                if ($type == 'to' && isset($metadata[$rcpt['email']])) {
                    if (!empty($metadata[$rcpt['email']]['tokens'])) {
                        $mergeVars = array(
                            'rcpt' => $rcpt['email'],
                            'vars' => array()
                        );
                        foreach ($metadata[$rcpt['email']]['tokens'] as $token => $value) {
                            $mergeVars['vars'][] = array(
                                'name'    => $mandrillMergeVars[$token],
                                'content' => $value
                            );
                        }
                        $rcptMergeVars[] = $mergeVars;

                        unset($mergeVars, $metadata[$rcpt['email']]['tokens']);
                    }

                    if (!empty($metadata[$rcpt['email']])) {
                        $rcptMetadata[] = array(
                            'rcpt'   => $rcpt['email'],
                            'values' => $metadata[$rcpt['email']]
                        );
                        unset($metadata[$rcpt['email']]);
                    }
                }
            }
        }

        $message['to'] = $recipients;
        unset($message['recipients']);

        // Set the merge vars
        $message['merge_vars'] = $rcptMergeVars;

        // Set the rest of $metadata as recipient_metadata
        $message['recipient_metadata'] = $rcptMetadata;

        // Set the reply to
        if (!empty($message['replyTo'])) {
            $message['headers']['Reply-To'] = $message['replyTo']['email'];
        }
        unset($message['replyTo']);

        // Package it up
        $payload = json_encode(
            array(
                'key'     => $this->getPassword(),
                'message' => $message
            )
        );

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders()
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function getApiEndpoint()
    {
        return 'https://mandrillapp.com/api/1.0/messages/send.json';
    }

    /**
     * {@inheritdoc}
     *
     * @param $response
     * @param $info
     *
     * @return array
     * @throws \Swift_TransportException
     */
    protected function handlePostResponse($response, $info)
    {
        $response = json_decode($response, true);

        $return = array();
        if (is_array($response)) {
            if (isset($response['status']) && $response['status'] == 'error') {
                throw new \Swift_TransportException($response['message']);
            }

            foreach ($response as $stat) {
                if (in_array($stat['status'], array('rejected', 'invalid'))) {
                    $return[] = $stat['email'];
                }
            }
        }

        return $return;
    }


    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'mandrill';
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        // Not used by Mandrill API
        return 0;
    }

    /**
     * @param \Swift_Message $message
     * @param int            $toBeAdded
     * @param string         $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        // Not used by Mandrill API
        return 0;
    }

    /**
     * Handle response
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return mixed
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $mandrillEvents = $request->request->get('mandrill_events');
        $mandrillEvents = json_decode($mandrillEvents, true);
        $bounces        = array(
            'hashIds' => array(),
            'emails'  => array()
        );

        if (is_array($mandrillEvents)) {
            foreach ($mandrillEvents as $event) {
                if (in_array($event['event'], array('hard_bounce', 'soft_bounce', 'reject'))) {
                    if (!empty($event['msg']['diag'])) {
                        $reason = $event['msg']['diag'];
                    } elseif (!empty($event['msg']['bounce_description'])) {
                        $reason = $event['msg']['bounce_description'];
                    } else {
                        $reason = $event['event'];
                    }

                    if (isset($event['msg']['metadata']['hashId'])) {
                        $bounces['hashIds'][$event['msg']['metadata']['hashId']] = $reason;
                    } else {
                        $bounces['emails'][$event['msg']['email']] = $reason;
                    }
                }
            }
        }

        return array('bounces' => $bounces);
    }
}
