<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Import.
 */
class Import extends FormEntity
{
    // Statuses:
    const CREATED     = 1;
    const IN_PROGRESS = 2;
    const IMPORTED    = 3;
    const FAILED      = 4;
    const STOPPED     = 5;

    // Priorities
    const LOW    = 512;
    const NORMAL = 64;
    const HIGH   = 1;

    /**
     * @var int
     */
    private $id;

    /**
     * Base directory of the import.
     *
     * @var string
     */
    private $dir;

    /**
     * File name of the CSV file which is in the $dir.
     *
     * @var string
     */
    private $file = 'import.csv';

    /**
     * Name of the original uploaded file.
     *
     * @var string
     */
    private $originalFile;

    /**
     * @var int
     */
    private $lineCount = 0;

    /**
     * @var int
     */
    private $processedLineCount = 0;

    /**
     * @var bool
     */
    private $priority;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $object = 'lead';

    /**
     * Array of fields to match [mautic_field_alias => csv_column_name].
     *
     * @var array
     */
    private $matchedFields = [];

    /**
     * @var array
     */
    private $properties = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->status   = self::CREATED;
        $this->priority = self::LOW;

        parent::__construct();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('imports')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\ImportRepository')
            ->addIndex(['object'], 'import_object')
            ->addIndex(['status'], 'import_status')
            ->addIndex(['priority'], 'import_priority')
            ->addId()
            ->addField('dir', Type::STRING)
            ->addField('file', Type::STRING)
            ->addNullableField('originalFile', Type::STRING, 'original_file')
            ->addField('lineCount', Type::INTEGER, 'line_count')
            ->addField('processedLineCount', Type::INTEGER, 'processed_line_count')
            ->addField('priority', Type::INTEGER)
            ->addField('status', Type::INTEGER)
            ->addField('object', Type::STRING)
            ->addNullableField('matchedFields', Type::JSON_ARRAY)
            ->addNullableField('properties', Type::JSON_ARRAY);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('dir', new Assert\NotBlank(
            ['message' => 'mautic.lead.import.dir.notblank']
        ));

        $metadata->addPropertyConstraint('file', new Assert\NotBlank(
            ['message' => 'mautic.lead.import.file.notblank']
        ));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('import')
            ->addListProperties(
                [
                    'id',
                    'dir',
                    'file',
                    'originalFile',
                    'lineCount',
                    'processedLineCount',
                    'priority',
                    'status',
                    'object',
                    'matchedFields',
                    'properties',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $dir
     *
     * @return Import
     */
    public function setDir($dir)
    {
        $this->isChanged('dir', $dir);
        $this->dir = $dir;

        return $this;
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param string $file
     *
     * @return Import
     */
    public function setFile($file)
    {
        $this->isChanged('file', $file);

        return $this->file = $file;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $originalFile
     *
     * @return Import
     */
    public function setOriginalFile($originalFile)
    {
        $this->isChanged('originalFile', $originalFile);

        return $this->originalFile = $originalFile;
    }

    /**
     * @return string
     */
    public function getOriginalFile()
    {
        return $this->originalFile;
    }

    /**
     * @param int $lineCount
     *
     * @return Import
     */
    public function setLineCount($lineCount)
    {
        $this->isChanged('lineCount', $lineCount);
        $this->lineCount = $lineCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getLineCount()
    {
        return $this->lineCount;
    }

    /**
     * @param int $processedLineCount
     *
     * @return Import
     */
    public function setProcessedLineCount($processedLineCount)
    {
        $this->isChanged('processedLineCount', $processedLineCount);
        $this->processedLineCount = $processedLineCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getProcessedLineCount()
    {
        return $this->processedLineCount;
    }

    /**
     * @param int $priority
     *
     * @return Import
     */
    public function setPriority($priority)
    {
        $this->isChanged('priority', $priority);
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $status
     *
     * @return Import
     */
    public function setStatus($status)
    {
        $this->isChanged('status', $status);
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $object
     *
     * @return Import
     */
    public function setObject($object)
    {
        $this->isChanged('object', $object);
        $this->object = $object;

        return $this;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $matchedFields
     *
     * @return Import
     */
    public function setMatchedFields($matchedFields)
    {
        $this->isChanged('matchedFields', $matchedFields);
        $this->matchedFields = $matchedFields;

        return $this;
    }

    /**
     * @return string
     */
    public function getMatchedFields()
    {
        return $this->properties;
    }

    /**
     * @param string $properties
     *
     * @return Import
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
