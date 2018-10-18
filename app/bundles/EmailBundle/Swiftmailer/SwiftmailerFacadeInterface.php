<?php

namespace Mautic\EmailBundle\Swiftmailer;

/**
 * Interface SwiftmailerFacadeInterface.
 */
interface SwiftmailerFacadeInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_Message $message);
}
