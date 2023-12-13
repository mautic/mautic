<?php

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

use Doctrine\DBAL\Connection;

/**
 * Help generate SQL statements to format column data.
 */
abstract class AbstractFormatter
{
    protected \Doctrine\DBAL\Platforms\AbstractPlatform $platform;

    protected string $name;

    /**
     * @return AbstractFormatter
     */
    public static function createFormatter(Connection $db)
    {
        $name  = $db->getDatabasePlatform()->getName();
        $class = '\Mautic\CoreBundle\Doctrine\QueryFormatter\\'.ucfirst($name).'Formatter';

        return new $class($db);
    }

    public function __construct(
        protected Connection $db
    ) {
        $this->platform = $this->db->getDatabasePlatform();
        $this->name     = $this->platform->getName();
    }

    /**
     * Format field to datetime.
     *
     * @param string $format
     *
     * @return mixed
     */
    abstract public function toDateTime($field, $format = 'Y-m-d H:i:s');

    /**
     * Format field to date.
     *
     * @param string $format
     *
     * @return mixed
     */
    abstract public function toDate($field, $format = 'Y-m-d');

    /**
     * Format field to time.
     *
     * @param string $format
     *
     * @return mixed
     */
    abstract public function toTime($field, $format = 'H:i:s');

    /**
     * Format field to a numeric.
     *
     * @return mixed
     */
    abstract public function toNumeric($field);
}
