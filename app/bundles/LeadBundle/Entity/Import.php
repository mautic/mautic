<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Import extends FormEntity
{
    /** ===== Statuses: ===== */
    /**
     * When the import entity is created for background processing.
     */
    public const QUEUED = 1;

    /**
     * When the background process started the import.
     */
    public const IN_PROGRESS = 2;

    /**
     * When the import is finished.
     */
    public const IMPORTED = 3;

    /**
     * When the import process failed.
     */
    public const FAILED = 4;

    /**
     * When the import has been stopped by a user.
     */
    public const STOPPED = 5;

    /**
     * When the import happens in the browser.
     */
    public const MANUAL = 6;

    /**
     * When the import is scheduled for later processing.
     */
    public const DELAYED = 7;

    /**
     * ===== Priorities: =====.
     */
    public const LOW    = 512;

    public const NORMAL = 64;

    public const HIGH   = 1;

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
     * @var string|null
     */
    private $originalFile;

    /**
     * Tolal line count of the CSV file.
     */
    private int $lineCount = 0;

    /**
     * Count of entities which were newly created.
     */
    private int $insertedCount = 0;

    /**
     * Count of entities which were updated.
     */
    private int $updatedCount = 0;

    /**
     * Count of ignored items.
     */
    private int $ignoredCount = 0;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var int
     */
    private $status;

    private ?\DateTimeInterface $dateStarted = null;

    private ?\DateTimeInterface $dateEnded = null;

    private string $object = 'lead';

    /**
     * @var array<mixed>|null
     */
    private $properties = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->status   = self::QUEUED;
        $this->priority = self::LOW;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('imports')
            ->setCustomRepositoryClass(ImportRepository::class)
            ->addIndex(['object'], 'import_object')
            ->addIndex(['status'], 'import_status')
            ->addIndex(['priority'], 'import_priority')
            ->addId()
            ->addField('dir', Types::STRING)
            ->addField('file', Types::STRING)
            ->addNullableField('originalFile', Types::STRING, 'original_file')
            ->addNamedField('lineCount', Types::INTEGER, 'line_count')
            ->addNamedField('insertedCount', Types::INTEGER, 'inserted_count')
            ->addNamedField('updatedCount', Types::INTEGER, 'updated_count')
            ->addNamedField('ignoredCount', Types::INTEGER, 'ignored_count')
            ->addField('priority', Types::INTEGER)
            ->addField('status', Types::INTEGER)
            ->addNullableField('dateStarted', Types::DATETIME_MUTABLE, 'date_started')
            ->addNullableField('dateEnded', Types::DATETIME_MUTABLE, 'date_ended')
            ->addField('object', Types::STRING)
            ->addNullableField('properties', Types::JSON);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
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
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('import')
            ->addListProperties(
                [
                    'id',
                    'dir',
                    'file',
                    'originalFile',
                    'lineCount',
                    'insertedCount',
                    'updatedCount',
                    'ignoredCount',
                    'priority',
                    'status',
                    'dateStarted',
                    'dateEnded',
                    'object',
                    'properties',
                ]
            )
            ->build();
    }

    /**
     * Checks if the import has everything needed to proceed.
     */
    public function canProceed(): bool
    {
        if (!in_array($this->getStatus(), [self::QUEUED, self::DELAYED])) {
            $this->setStatusInfo('Import could not be triggered since it is not queued nor delayed');

            return false;
        }

        if (false === file_exists($this->getFilePath()) || false === is_readable($this->getFilePath())) {
            $this->setStatus(self::FAILED);
            $this->setStatusInfo($this->getFile().' not found');

            return false;
        }

        return true;
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
     * Decides if this import entity is triggered as the background
     * job or as UI process.
     */
    public function isBackgroundProcess(): bool
    {
        return !(self::MANUAL === $this->getStatus());
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
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get import file path.
     */
    public function getFilePath(): string
    {
        return $this->getDir().'/'.$this->getFile();
    }

    /**
     * Set import file path.
     *
     * @param string $path
     *
     * @return Import
     */
    public function setFilePath($path)
    {
        $fileName = basename($path);
        $dir      = substr($path, 0, -1 * (strlen($fileName) + 1));

        $this->setDir($dir);
        $this->setFile($fileName);

        return $this;
    }

    /**
     * Removes the file if exists.
     * It won't throw any exception if the file is not readable.
     * Not removing the CSV file is not considered a big trouble.
     * It will be removed on the next cache:clear.
     */
    public function removeFile(): void
    {
        $file = $this->getFilePath();

        if (file_exists($file) && is_writable($file)) {
            unlink($file);
        }
    }

    /**
     * @param string $originalFile
     *
     * @return Import
     */
    public function setOriginalFile($originalFile)
    {
        $this->isChanged('originalFile', $originalFile);
        $this->originalFile = $originalFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFile()
    {
        return $this->originalFile;
    }

    /**
     * getName method is used by standard templates so there it is for this entity.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getOriginalFile() ?: $this->getId();
    }

    public function setLineCount(int $lineCount): self
    {
        $this->isChanged('lineCount', $lineCount);
        $this->lineCount = $lineCount;

        return $this;
    }

    public function getLineCount(): int
    {
        return $this->lineCount;
    }

    public function setInsertedCount(int $insertedCount): self
    {
        $this->isChanged('insertedCount', $insertedCount);
        $this->insertedCount = $insertedCount;

        return $this;
    }

    public function increaseInsertedCount(): self
    {
        return $this->setInsertedCount($this->insertedCount + 1);
    }

    public function getInsertedCount(): int
    {
        return $this->insertedCount;
    }

    public function setUpdatedCount(int $updatedCount): self
    {
        $this->isChanged('updatedCount', $updatedCount);
        $this->updatedCount = $updatedCount;

        return $this;
    }

    public function increaseUpdatedCount(): self
    {
        return $this->setUpdatedCount($this->updatedCount + 1);
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function setIgnoredCount(int $ignoredCount): self
    {
        $this->isChanged('ignoredCount', $ignoredCount);
        $this->ignoredCount = $ignoredCount;

        return $this;
    }

    public function increaseIgnoredCount(): self
    {
        return $this->setIgnoredCount($this->ignoredCount + 1);
    }

    public function getIgnoredCount(): int
    {
        return $this->ignoredCount;
    }

    /**
     * Counts how many rows have been processed so far.
     */
    public function getProcessedRows(): int
    {
        return $this->getInsertedCount() + $this->getUpdatedCount() + $this->getIgnoredCount();
    }

    /**
     * Counts current progress percentage.
     */
    public function getProgressPercentage(): float|int
    {
        $processed = $this->getProcessedRows();

        if ($processed && $total = $this->getLineCount()) {
            return round(($processed / $total) * 100, 2);
        }

        return 0.0;
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
     * Returns Twitter Bootstrap label class based on current status.
     */
    public function getSatusLabelClass(): string
    {
        return match ($this->status) {
            self::QUEUED => 'info',
            self::IN_PROGRESS, self::MANUAL => 'primary',
            self::IMPORTED => 'success',
            self::FAILED   => 'danger',
            self::STOPPED, self::DELAYED => 'warning',
            default => 'default',
        };
    }

    public function setDateStarted(?\DateTimeInterface $dateStarted): self
    {
        $this->isChanged('dateStarted', $dateStarted);
        $this->dateStarted = $dateStarted;

        return $this;
    }

    public function getDateStarted(): ?\DateTimeInterface
    {
        return $this->dateStarted;
    }

    /**
     * Modify the entity for the start of import.
     */
    public function start(): self
    {
        if (empty($this->getDateStarted())) {
            $this->setDateStarted(new \DateTime());
        }

        $this->setStatus(self::IN_PROGRESS);

        return $this;
    }

    /**
     * Modify the entity for the end of import.
     */
    public function end($removeFile = true): self
    {
        $this->setDateEnded(new \DateTime());

        if (self::IN_PROGRESS === $this->getStatus()) {
            $this->setStatus(self::IMPORTED);

            if ($removeFile) {
                $this->removeFile();
            }
        }

        return $this;
    }

    public function setDateEnded(?\DateTimeInterface $dateEnded): self
    {
        $this->isChanged('dateEnded', $dateEnded);
        $this->dateEnded = $dateEnded;

        return $this;
    }

    public function getDateEnded(): ?\DateTimeInterface
    {
        return $this->dateEnded;
    }

    /**
     * Counts how long the import has run so far.
     *
     * @return \DateInterval|null
     */
    public function getRunTime()
    {
        $startTime = $this->getDateStarted();
        $endTime   = $this->getDateEnded();

        if (!$endTime && self::IN_PROGRESS === $this->getStatus()) {
            $endTime = $this->getDateModified();
        }

        if ($startTime instanceof \DateTimeInterface && $endTime instanceof \DateTimeInterface) {
            return $endTime->diff($startTime);
        }

        return null;
    }

    /**
     * Returns run time in seconds.
     *
     * @return int
     */
    public function getRunTimeSeconds()
    {
        $startTime = $this->getDateStarted();
        $endTime   = $this->getDateEnded();

        if (!$endTime && self::IN_PROGRESS === $this->getStatus()) {
            $endTime = $this->getDateModified();
        }

        if ($startTime instanceof \DateTime && $endTime instanceof \DateTime) {
            return $endTime->format('U') - $startTime->format('U');
        }

        return 0;
    }

    /**
     * Counts speed in items per second.
     */
    public function getSpeed(): float
    {
        $runtime       = $this->getRunTimeSeconds();
        $processedRows = $this->getProcessedRows();

        if ($runtime && $processedRows) {
            return round($processedRows / $runtime, 2);
        }

        return (float) $processedRows;
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
     * @return Import
     */
    public function setMatchedFields(array $fields)
    {
        $properties           = $this->properties;
        $properties['fields'] = $fields;

        return $this->setProperties($properties);
    }

    public function setLastLineImported($line): void
    {
        $this->properties['line'] = (int) $line;
    }

    /**
     * @return int
     */
    public function getLastLineImported()
    {
        return $this->properties['line'] ?? 0;
    }

    /**
     * @return array
     */
    public function getMatchedFields()
    {
        return empty($this->properties['fields']) ? [] : $this->properties['fields'];
    }

    /**
     * @param array $properties
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
     * @param array<mixed> $properties
     *
     * @return Import
     */
    public function mergeToProperties($properties)
    {
        return $this->setProperties(array_merge($this->properties, $properties));
    }

    /**
     * Get array of default values.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->properties['defaults'] ?? [];
    }

    /**
     * Set a default value to the defaults array.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Import
     */
    public function setDefault($key, $value)
    {
        return $this->mergeToProperties([
            'defaults' => array_merge($this->getDefaults(), [$key => $value]),
        ]);
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getDefault($key)
    {
        return empty($this->properties['defaults'][$key]) ? null : $this->properties['defaults'][$key];
    }

    /**
     * Set headers array to the properties.
     *
     * @return Import
     */
    public function setHeaders(array $headers)
    {
        $properties            = $this->properties;
        $properties['headers'] = $headers;

        return $this->setProperties($properties);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return empty($this->properties['headers']) ? [] : $this->properties['headers'];
    }

    /**
     * Set parser config array to the properties.
     *
     * @return Import
     */
    public function setParserConfig(array $parser)
    {
        $properties           = $this->properties;
        $properties['parser'] = $parser;

        return $this->setProperties($properties);
    }

    /**
     * @return array
     */
    public function getParserConfig()
    {
        return empty($this->properties['parser']) ? [] : $this->properties['parser'];
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function getStatusInfo()
    {
        return empty($this->properties['status_info']) ? 'unknown' : $this->properties['status_info'];
    }

    /**
     * @param string $info
     *
     * @return Import
     */
    public function setStatusInfo($info)
    {
        $properties                = $this->properties;
        $properties['status_info'] = $info;

        return $this->setProperties($properties);
    }

    /**
     * Overwrite this method so we could change import status based on it.
     *
     * @param bool $isPublished
     *
     * @return $this
     */
    public function setIsPublished($isPublished)
    {
        if ($isPublished && self::STOPPED === $this->getStatus()) {
            $this->setStatus(self::QUEUED);
        }

        if (!$isPublished && (self::IN_PROGRESS === $this->getStatus() || self::QUEUED === $this->getStatus())) {
            $this->setStatus(self::STOPPED);
        }

        return parent::setIsPublished($isPublished);
    }

    /**
     * Get pie graph data for row status counts.
     *
     * @return array{labels: mixed[], datasets: mixed[]}
     */
    public function getRowStatusesPieChart(Translator $translator): array
    {
        $chart = new PieChart();
        $chart->setDataset($translator->trans('mautic.lead.import.inserted.count'), $this->getInsertedCount());
        $chart->setDataset($translator->trans('mautic.lead.import.updated.count'), $this->getUpdatedCount());
        $chart->setDataset($translator->trans('mautic.lead.import.ignored.count'), $this->getIgnoredCount());

        return $chart->render();
    }
}
