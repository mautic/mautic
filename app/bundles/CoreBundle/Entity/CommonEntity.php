<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/17/14
 * Time: 12:55 PM
 */

namespace Mautic\CoreBundle\Entity;


class CommonEntity
{
    /**
     * Wrapper function for isProperty methods
     *
     * @param string $name
     * @param        $arguments
     *
     * @throws \InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'is') === 0 && method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        } elseif ($name == 'getName' && method_exists($this, 'getTitle')) {
            return $this->getTitle();
        }

        throw new \InvalidArgumentException('Method ' . $name . ' not exists');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = get_called_class();
        if (method_exists($this, 'getId')) {
            $string .= " with ID #" . $this->getId();
        }

        return $string;
    }
}