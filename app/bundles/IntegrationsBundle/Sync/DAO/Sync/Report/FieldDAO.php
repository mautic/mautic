<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class FieldDAO
{
    const FIELD_CHANGED   = 'changed';
    const FIELD_REQUIRED  = 'required';
    const FIELD_UNCHANGED = 'unchanged';

    /**
     * @var string
     */
    private $name;

    /**
     * @var NormalizedValueDAO
     */
    private $value;

    /**
     * @var null|\DateTimeInterface
     */
    private $changeDateTime = null;

    /**
     * @var string
     */
    private $state;

    /**
     * @param string             $name
     * @param NormalizedValueDAO $value
     * @param string             $state
     */
    public function __construct(string $name, NormalizedValueDAO $value, string $state = self::FIELD_CHANGED)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return NormalizedValueDAO
     */
    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getChangeDateTime(): ?\DateTimeInterface
    {
        return $this->changeDateTime;
    }

    /**
     * @param \DateTimeInterface $changeDateTime
     *
     * @return FieldDAO
     */
    public function setChangeDateTime(\DateTimeInterface $changeDateTime): self
    {
        $this->changeDateTime = $changeDateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }
}
