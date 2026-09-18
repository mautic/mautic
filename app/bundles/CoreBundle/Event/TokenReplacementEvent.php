<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\DynamicContentBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

class TokenReplacementEvent extends CommonEvent
{
    /**
     * @var CommonEntity|string|null
     */
    protected $entity;

    /**
     * @var CommonEntity|string|null
     */
    protected array|string $content;

    /**
     * @var array
     */
    protected $tokens = [];

    private ?Stat $stat = null;

    /**
     * @param mixed $passthrough
     * @param \Mautic\LeadBundle\Entity\Lead|mixed[]|string|null $content
     */
    public function __construct(
        array|string $content,
        protected \Mautic\LeadBundle\Entity\Lead|array|null $lead = null,
        protected array $clickthrough = [],
        protected $passthrough = null,
        private readonly bool $internalSend = false,
    ) {
        if ($content instanceof CommonEntity) {
            $this->entity = $content;
        }

        $this->content      = $content;
    }

    /**
     * @return CommonEntity|string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return Lead|mixed[]|null
     */
    public function getLead(): \Mautic\LeadBundle\Entity\Lead|array|null
    {
        return $this->lead;
    }

    /**
     * @return mixed[]
     */
    public function getClickthrough(): array
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
    public function setClickthrough(array $clickthrough): void
    {
        $this->clickthrough = $clickthrough;
    }

    /**
     * @return CommonEntity|string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function addToken($token, $value): void
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

    public function getStat(): ?Stat
    {
        return $this->stat;
    }

    public function setStat(?Stat $stat): void
    {
        $this->stat = $stat;
    }

    public function isInternalSend(): bool
    {
        return $this->internalSend;
    }
}
