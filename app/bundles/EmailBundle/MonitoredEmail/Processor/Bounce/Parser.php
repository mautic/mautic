<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;

class Parser
{
    /**
     * @var Message
     */
    private $message;

    /**
     * Parser constructor.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return string|null
     */
    public function getFailedRecipients()
    {
        return (isset($this->message->xHeaders['x-failed-recipients'])) ? $this->message->xHeaders['x-failed-recipients'] : null;
    }

    /**
     * @return BouncedEmail
     *
     * @throws BounceNotFound
     */
    public function parse()
    {
        $bouncerAddress = null;
        foreach ($this->message->to as $to => $name) {
            // Some ISPs strip the + email so will still process the content for a bounce
            // even if a +bounce address was not found
            if (false !== strpos($to, '+bounce')) {
                $bouncerAddress = $to;

                break;
            }
        }

        // First parse for a DSN report
        $dsnParser = new DsnParser();
        try {
            $bounce = $dsnParser->getBounce($this->message);
        } catch (BounceNotFound $exception) {
            // DSN report wasn't found so try parsing the body itself
            $bodyParser = new BodyParser();
            $bounce     = $bodyParser->getBounce($this->message, $this->getFailedRecipients());
        }

        $bounce->setBounceAddress($bouncerAddress);

        return $bounce;
    }
}
