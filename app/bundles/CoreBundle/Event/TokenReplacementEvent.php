<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

class TokenReplacementEvent extends CommonEvent
{
    /**
     * @var CommonEntity|string
     */
    protected $entity;

    /**
     * @var CommonEntity|string|null
     */
    protected $content;

    /**
     * @var array
     */
    protected $clickthrough = [];

    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @param CommonEntity|string|null $content
     * @param Lead|mixed[]|null        $lead
     */
    public function __construct($content, protected array|Lead|null $lead = null, array $clickthrough = [], protected mixed $passthrough = null, private bool $internalSend = false)
    {
        if ($content instanceof CommonEntity) {
            $this->entity = $content;
        }

        $this->content      = $content;
        $this->clickthrough = $clickthrough;
    }

    public function getContent(): CommonEntity|string|null
    {
        return $this->content;
    }

    public function setContent(CommonEntity|string|null $content)
    {
        $this->content = $content;
    }

    /**
     * @return Lead|mixed[]|null
     */
    public function getLead(): Lead|array|null
    {
        return $this->lead;
    }

    /**
     * @return mixed[]
     */
    public function getClickthrough()
    {
        if (!in_array('lead', $this->clickthrough)) {
            if (is_array($this->lead) && !empty($this->lead['id'])) {
                $this->clickthrough['lead'] = $this->lead['id'];
            } elseif ($this->lead instanceof Lead && $this->lead->getId()) {
                $this->clickthrough['lead'] = $this->lead->getId();
            }
        }

        return $this->clickthrough;
    }

    /**
     * @param mixed[] $clickthrough
     */
    public function setClickthrough($clickthrough)
    {
        $this->clickthrough = $clickthrough;
    }

    public function getEntity(): CommonEntity|string
    {
        return $this->entity;
    }

    public function addToken($token, $value)
    {
        $this->tokens[$token] = $value;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @return mixed|null
     */
    public function getPassthrough()
    {
        return $this->passthrough;
    }

    public function isInternalSend(): bool
    {
        return $this->internalSend;
    }
}
