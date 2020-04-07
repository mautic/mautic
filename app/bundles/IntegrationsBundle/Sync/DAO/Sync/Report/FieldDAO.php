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

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

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
     * @var \DateTimeInterface|null
     */
    private $changeDateTime;

    /**
     * @var string
     */
    private $state;

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

    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
    }

    public function getChangeDateTime(): ?\DateTimeInterface
    {
        return $this->changeDateTime;
    }

    /**
     * @return FieldDAO
     */
    public function setChangeDateTime(\DateTimeInterface $changeDateTime): self
    {
        $this->changeDateTime = $changeDateTime;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
