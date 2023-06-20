<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Exception\ResponseItemException;
use Symfony\Component\HttpFoundation\Request;

class ResponseItems implements \Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var ResponseItem[]
     */
    private $items = [];

    public function __construct(Request $request)
    {
        $payload = $request->request->all();
        foreach ($payload as $item) {
            if (empty($item['event']) || !CallbackEnum::shouldBeEventProcessed($item['event'])) {
                continue;
            }
            try {
                $this->items[] = new ResponseItem($item);
            } catch (ResponseItemException $e) {
            }
        }
    }

    /**
     * Return the current element.
     *
     * @see  http://php.net/manual/en/iterator.current.php
     */
    public function current(): ResponseItem
    {
        return $this->items[$this->position];
    }

    /**
     * Move forward to next element.
     *
     * @see  http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element.
     *
     * @see  http://php.net/manual/en/iterator.key.php
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see  http://php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see  http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->position = 0;
    }
}
