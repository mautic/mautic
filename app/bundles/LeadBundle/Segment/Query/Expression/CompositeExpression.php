<?php

namespace Mautic\LeadBundle\Segment\Query\Expression;

/**
 * Composite expression is responsible to build a group of similar expression. Mautic MOD.
 *
 * @see   www.doctrine-project.org
 * @since  2.1
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Jan Kozak <galvani78@gmail.com>
 */
class CompositeExpression implements \Countable
{
    /**
     * Constant that represents an AND composite expression.
     */
    const TYPE_AND = 'AND';

    /**
     * Constant that represents an OR composite expression.
     */
    const TYPE_OR  = 'OR';

    /**
     * The instance type of composite expression.
     *
     * @var string
     */
    private $type;

    /**
     * Each expression part of the composite expression.
     *
     * @var array
     */
    private $parts = [];

    /**
     * Constructor.
     *
     * @param string $type  instance type of composite expression
     * @param array  $parts composition of expressions to be joined on composite expression
     */
    public function __construct($type, array $parts = [])
    {
        $this->type = $type;

        $this->addMultiple($parts);
    }

    /**
     * Adds multiple parts to composite expression.
     *
     * @return self
     */
    public function addMultiple(array $parts = [])
    {
        foreach ((array) $parts as $part) {
            $this->add($part);
        }

        return $this;
    }

    /**
     * Adds an expression to composite expression.
     *
     * @param mixed $part
     *
     * @return self
     */
    public function add($part)
    {
        if (!empty($part) || ($part instanceof self && $part->count() > 0)) {
            $this->parts[] = $part;
        }

        return $this;
    }

    /**
     * Retrieves the amount of expressions on composite expression.
     *
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * Retrieves the string representation of this composite expression.
     *
     * @return string
     */
    public function __toString()
    {
        if (1 === count($this->parts)) {
            return (string) $this->parts[0];
        }

        return '('.implode(') '.$this->type.' (', $this->parts).')';
    }

    /**
     * Returns the type of this composite expression (AND/OR).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
