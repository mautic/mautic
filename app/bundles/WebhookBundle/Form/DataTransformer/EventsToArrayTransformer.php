<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Form\DataTransformer;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\Event;

/**
 * Class EventsToArrayTransformer
 */
class EventsToArrayTransformer implements DataTransformerInterface
{
    private $webhook;

    public function __construct(Webhook $webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * Convert from the PersistentCollection of Event entities to a simple array
     *
     * @return array
     */
    public function transform($event)
    {
        return $event->getEventType();
    }

    /**
     * Convert a simple array into a PersistentCollection of Event entities
     *
     * @return PersistentCollection
     */
    public function reverseTransform($submittedArray)
    {
        // Set the webhoook and event type, basically update the selected event   
        $event = new Event();
        $event->setWebhook($this->webhook)->setEventType($submittedArray);
        $this->webhook->setEvent($event);

        return $event;
    }
}
