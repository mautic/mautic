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
     *
     * @return BouncedEmail
     */
    public function setContactEmail($email)
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
     *
     * @return BouncedEmail
     */
    public function setRuleCategory($ruleCategory)
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
     *
     * @return BouncedEmail
     */
    public function setRuleNumber($ruleNumber)
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
     *
     * @return BouncedEmail
     */
    public function setType($bounceType)
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
     *
     * @return BouncedEmail
     */
    public function setIsFinal($final)
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

    /**
     * @return BouncedEmail
     */
    public function setBounceAddress($bounceAddress)
    {
        $this->bounceAddress = $bounceAddress;

        return $this;
    }
}
