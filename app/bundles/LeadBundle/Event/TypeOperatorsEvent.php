<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects operators for different field types.
 */
final class TypeOperatorsEvent extends Event
{
    private $operators = [];

    /**
     * $operators example:
     * [
     *      'include' => ['=' => 'like'],
     *      'exclude' => ['!=' => '!like'],
     * ].
     */
    public function setOperatorsForFieldType(string $fieldType, array $operators): void
    {
        $this->operators[$fieldType] = $operators;
    }

    public function getOperatorsForAllFieldTypes(): array
    {
        return $this->operators;
    }
}
