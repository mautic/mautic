<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MauticSyncFieldsLoadEvent extends Event
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $objectName;

    public function __construct(string $objectName, array $fields)
    {
        $this->objectName = $objectName;
        $this->fields     = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $key, string $name): void
    {
        $this->fields[$key] = $name;
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }
}
