<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;

class BouncedEmail
{
    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $ruleCategory;

    /**
     * @var string|null
     */
    private $ruleNumber;

    /**
     * @var string|null
     */
    private $bounceType;

    private bool $final = false;

    /**
     * @var string|null
     */
    private $bounceAddress;

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setContactEmail($email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getRuleCategory()
    {
        return $this->ruleCategory;
    }

    /**
     * @param string $ruleCategory
     */
    public function setRuleCategory($ruleCategory): static
    {
        $this->ruleCategory = $ruleCategory;

        return $this;
    }

    /**
     * @return string
     */
    public function getRuleNumber()
    {
        return $this->ruleNumber;
    }

    /**
     * @param string $ruleNumber
     */
    public function setRuleNumber($ruleNumber): static
    {
        $this->ruleNumber = $ruleNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->bounceType;
    }

    /**
     * @param mixed $bounceType
     */
    public function setType($bounceType): static
    {
        $this->bounceType = $bounceType;

        return $this;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * @param bool $final
     */
    public function setIsFinal($final): static
    {
        $this->final = (bool) $final;

        return $this;
    }

    /**
     * @return string
     */
    public function getBounceAddress()
    {
        return $this->bounceAddress;
    }

    public function setBounceAddress($bounceAddress): static
    {
        $this->bounceAddress = $bounceAddress;

        return $this;
    }
}
