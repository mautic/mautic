<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Crate;

use Mautic\LeadBundle\Helper\FormFieldHelper;

final class FieldCrate
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $properties;

    public function __construct(string $key, string $name, string $type, array $properties)
    {
        $this->key        = $key;
        $this->name       = $name;
        $this->type       = $type;
        $this->properties = $properties;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function isListType(): bool
    {
        $isListType    = in_array($this->getType(), FormFieldHelper::getListTypes());
        $hasList       = !empty($this->getProperties()['list']);
        $hasOptionList = !empty($this->getProperties()['optionlist']);

        return $isListType || $hasList || $hasOptionList;
    }
}
