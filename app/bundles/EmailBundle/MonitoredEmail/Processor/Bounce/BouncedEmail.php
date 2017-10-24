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
    protected $email;

    /**
     * @var
     */
    protected $ruleCategory;

    /**
     * @var
     */
    protected $ruleNumber;

    /**
     * @var
     */
    protected $bounceType;

    /**
     * @var int
     */
    protected $final = 0;

    /**
     * @var
     */
    protected $bounceAddress;

    /**
     * @return mixed
     */
    public function getContactEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return BouncedEmail
     */
    public function setContactEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRuleCategory()
    {
        return $this->ruleCategory;
    }

    /**
     * @param mixed $ruleCategory
     *
     * @return BouncedEmail
     */
    public function setRuleCategory($ruleCategory)
    {
        $this->ruleCategory = $ruleCategory;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRuleNumber()
    {
        return $this->ruleNumber;
    }

    /**
     * @param mixed $ruleNumber
     *
     * @return BouncedEmail
     */
    public function setRuleNumber($ruleNumber)
    {
        $this->ruleNumber = $ruleNumber;

        return $this;
    }

    /**
     * @return mixed
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
        $this->final = (int) $final;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBounceAddress()
    {
        return $this->bounceAddress;
    }

    /**
     * @param mixed $originalTo
     *
     * @return BouncedEmail
     */
    public function setBounceAddress($bounceAddress)
    {
        $this->bounceAddress = $bounceAddress;

        return $this;
    }
}
