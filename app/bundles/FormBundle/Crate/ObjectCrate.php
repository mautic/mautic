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

final class ObjectCrate
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $key, string $name)
    {
        $this->key  = $key;
        $this->name = $name;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
