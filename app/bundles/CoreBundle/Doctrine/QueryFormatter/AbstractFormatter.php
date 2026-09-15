<?php

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

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
        $name  = DatabasePlatform::getDatabasePlatform($db->getDatabasePlatform());
        $class = '\Mautic\CoreBundle\Doctrine\QueryFormatter\\'.ucfirst($name).'Formatter';

        return new $class($db);
    }

    public function __construct(
        protected Connection $db,
    ) {
        $this->platform = $this->db->getDatabasePlatform();
        $this->name     = DatabasePlatform::getDatabasePlatform($this->platform);
    }

    /**
     * Format field to datetime.
     *
     * @return mixed
     */
    abstract public function toDateTime($field, string $format = 'Y-m-d H:i:s');

    /**
     * Format field to date.
     *
     * @return mixed
     */
    abstract public function toDate($field, string $format = 'Y-m-d');

    /**
     * Format field to time.
     *
     * @return mixed
     */
    abstract public function toTime($field, string $format = 'H:i:s');

    /**
     * Format field to a numeric.
     *
     * @return mixed
     */
    abstract public function toNumeric($field);
}
