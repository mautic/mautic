<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WebhookBuilderEvent.
 */
class WebhookBuilderEvent extends Event
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Add an event for the event list.
     *
     * @param string $key   - a unique identifier; it is recommended that it be namespaced i.e. lead.mytrigger
     * @param array  $event - can contain the following keys:
     *                      'label'       => (required) what to display in the list
     *                      'description' => (optional) short description of event
     */
    public function addEvent($key, array $event)
    {
        if (array_key_exists($key, $this->events)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another webhook event. Please use a different key.");
        }

        $event['label']       = $this->translator->trans($event['label']);
        $event['description'] = (isset($event['description'])) ? $this->translator->trans($event['description']) : '';

        $this->events[$key] = $event;
    }

    /**
     * Get webhook events.
     *
     * @return array
     */
    public function getEvents()
    {
        static $sorted = false;

        if (empty($sorted)) {
            uasort($this->events, function ($a, $b) {
                return strnatcasecmp(
                    $a['label'], $b['label']);
            });
            $sorted = true;
        }

        return $this->events;
    }
}
