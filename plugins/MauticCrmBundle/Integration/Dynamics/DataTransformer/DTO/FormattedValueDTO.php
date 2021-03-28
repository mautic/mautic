<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration\Dynamics\DataTransformer\DTO;

class FormattedValueDTO
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string|bool
     */
    private $target;

    public function __construct(string $key, $value, array $field)
    {
        $this->key    = $key;
        $this->value  = $value;
        $this->target = $field['target'] ?? false;
    }

    public function getKeyForPayload(): string
    {
        if ($this->isLookupType()) {
            return sprintf('%s@odata.bind', $this->key);
        }

        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValueForPayload()
    {
        if ($this->isLookupType()) {
            return sprintf('/%ss(%s)', $this->target, $this->value);
        }

        return $this->value;
    }

    public function isLookupType(): bool
    {
        if ($this->target) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
