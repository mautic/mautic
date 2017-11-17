<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;

/**
 * Class BouncedEmail.
 */
class BouncedEmail
{
    /**
     * @var
     */
    private $email;

    /**
     * @var
     */
    private $ruleCategory;

    /**
     * @var
     */
    private $ruleNumber;

    /**
     * @var
     */
    private $bounceType;

    /**
     * @var int
     */
    private $final = 0;

    /**
     * @var
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

    /**
     * @return int
     */
    public function isFinal()
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
     * @param string $originalTo
     *
     * @return BouncedEmail
     */
    public function setBounceAddress($bounceAddress)
    {
        $this->bounceAddress = $bounceAddress;

        return $this;
    }
}
