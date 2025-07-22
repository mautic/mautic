<?php

namespace Mautic\AssetBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "asset:read"
 *        },
 *       "swagger_definition_name"="Read",
 *       "api_included"={"category"}
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "asset:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Asset extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int|null
     *
     * @Groups({"asset:read", "download:read", "email:read"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $title;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $description;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $storageLocation = 'local';

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $path;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $remotePath;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $originalFileName;

    /**
     * @var File
     */
    private $file;

    /**
     * Holds upload directory.
     */
    private $uploadDir;

    /**
     * Holds max size of uploaded file.
     */
    private $maxSize;

    /**
     * Temporary location when asset file is beeing updated.
     * We need to keep the old file till we are sure the new
     * one is stored correctly.
     */
    private $temp;

    /**
     * Temporary ID used for file upload and validations
     * before the actual ID is known.
     */
    private $tempId;

    /**
     * Temporary file name used for file upload and validations
     * before the actual ID is known.
     */
    private $tempName;

    /**
     * @var string
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $alias;

    /**
     * @var string
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $language = 'en';

    /**
     * @var \DateTimeInterface|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $publishUp;

    /**
     * @var \DateTimeInterface|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $publishDown;

    /**
     * @var int
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $downloadCount = 0;

    /**
     * @var int
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $uniqueDownloadCount = 0;

    /**
     * @var int
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $revision = 1;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     **/
    private $category;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $extension;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $mime;

    /**
     * @var int|null
     */
    private $size;

    /**
     * @var string|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $downloadUrl;

    /**
     * @var bool|null
     *
     * @Groups({"asset:read", "asset:write", "download:read", "email:read"})
     */
    private $disallow = true;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('assets')
            ->setCustomRepositoryClass(AssetRepository::class)
            ->addIndex(['alias'], 'asset_alias_search');

        $builder->addIdColumns('title');

        $builder->addField('alias', 'string');

        $builder->createField('storageLocation', 'string')
            ->columnName('storage_location')
            ->nullable()
            ->build();

        $builder->createField('path', 'string')
            ->nullable()
            ->build();

        $builder->createField('remotePath', Types::TEXT)
            ->columnName('remote_path')
            ->nullable()
            ->build();

        $builder->createField('originalFileName', Types::TEXT)
            ->columnName('original_file_name')
            ->nullable()
            ->build();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->addPublishDates();

        $builder->createField('downloadCount', 'integer')
            ->columnName('download_count')
            ->build();

        $builder->createField('uniqueDownloadCount', 'integer')
            ->columnName('unique_download_count')
            ->build();

        $builder->addField('revision', 'integer');

        $builder->addCategory();

        $builder->createField('extension', 'string')
            ->nullable()
            ->build();

        $builder->createField('mime', 'string')
            ->nullable()
            ->build();

        $builder->createField('size', 'integer')
            ->nullable()
            ->build();

        $builder->createField('disallow', 'boolean')
            ->nullable()
            ->build();

        static::addUuidField($builder);
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('asset')
            ->addListProperties(
                [
                    'id',
                    'title',
                    'alias',
                    'category',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'language',
                    'publishUp',
                    'publishDown',
                    'downloadCount',
                    'uniqueDownloadCount',
                    'revision',
                    'extension',
                    'mime',
                    'size',
                    'downloadUrl',
                    'storageLocation',
                    'disallow',
                ]
            )
            ->build();
    }

    /**
     * Clone magic function.
     */
    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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
     * Sets file.
     */
    public function setFile(File $file = null): void
    {
        $this->file = $file;

        // check if we have an old asset path
        if (isset($this->path)) {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        }
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        // if file is not set, try to find it at temp folder
        if ($this->isLocal() && empty($this->file)) {
            $tempFile = $this->loadFile(true);

            if ($tempFile) {
                $this->setFile($tempFile);
            }
        }

        return $this->file;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Asset
     */
    public function setTitle($title)
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param mixed $extension
     */
    public function setExtension($extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return mixed
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * @param mixed $mime
     */
    public function setMime($mime): void
    {
        $this->mime = $mime;
    }

    /**
     * Set originalFileName.
     *
     * @param string $originalFileName
     *
     * @return Asset
     */
    public function setOriginalFileName($originalFileName)
    {
        $this->isChanged('originalFileName', $originalFileName);
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * Get originalFileName.
     *
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * Set storage location.
     *
     * @param string $storageLocation
     *
     * @return Asset
     */
    public function setStorageLocation($storageLocation)
    {
        $this->isChanged('storageLocation', $storageLocation);
        $this->storageLocation = $storageLocation;

        return $this;
    }

    /**
     * Get storage location.
     *
     * @return string
     */
    public function getStorageLocation()
    {
        if (null === $this->storageLocation) {
            $this->storageLocation = 'local';
        }

        return $this->storageLocation;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return Asset
     */
    public function setPath($path)
    {
        $this->isChanged('path', $path);
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set remote path.
     *
     * @param string $remotePath
     *
     * @return Asset
     */
    public function setRemotePath($remotePath)
    {
        $this->isChanged('remotePath', $remotePath);
        $this->remotePath = $remotePath;

        return $this;
    }

    /**
     * Get remote path.
     *
     * @return string
     */
    public function getRemotePath()
    {
        return $this->remotePath;
    }

    /**
     * Set alias.
     *
     * @param string $alias
     *
     * @return Asset
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return Asset
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTimeInterface $publishDown
     *
     * @return Asset
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * Set downloadCount.
     *
     * @param int $downloadCount
     *
     * @return Asset
     */
    public function setDownloadCount($downloadCount)
    {
        $this->downloadCount = $downloadCount;

        return $this;
    }

    /**
     * Get downloadCount.
     *
     * @return int
     */
    public function getDownloadCount()
    {
        return $this->downloadCount;
    }

    /**
     * Set revision.
     *
     * @param int $revision
     *
     * @return Asset
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get revision.
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return Asset
     */
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set category.
     *
     * @return Asset
     */
    public function setCategory(\Mautic\CategoryBundle\Entity\Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set uniqueDownloadCount.
     *
     * @param int $uniqueDownloadCount
     *
     * @return Asset
     */
    public function setUniqueDownloadCount($uniqueDownloadCount)
    {
        $this->uniqueDownloadCount = $uniqueDownloadCount;

        return $this;
    }

    /**
     * Get uniqueDownloadCount.
     *
     * @return int
     */
    public function getUniqueDownloadCount()
    {
        return $this->uniqueDownloadCount;
    }

    public function setFileNameFromRemote(): void
    {
        $fileName = basename($this->getRemotePath());

        $this->setOriginalFileName($fileName);

        // set the asset title as original file name if title is missing
        if (null === $this->getTitle()) {
            $this->setTitle($fileName);
        }
    }

    public function preUpload(): void
    {
        if (null !== $this->getFile()) {
            // set the asset title as original file name if title is missing
            if (null === $this->getTitle()) {
                $this->setTitle($this->file->getClientOriginalName());
            }

            $filename  = sha1(uniqid(mt_rand(), true));
            $extension = $this->getFile()->guessExtension();

            if (empty($extension)) {
                // get it from the original name
                $extension = pathinfo($this->originalFileName, PATHINFO_EXTENSION);
            }
            $this->path = $filename.'.'.$extension;
        } elseif ($this->isRemote() && null !== $this->getRemotePath()) {
            $this->setFileNameFromRemote();
        }
    }

    public function upload(): void
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            // check for the remote and set type data
            if ($this->isRemote()) {
                $this->setFileInfoFromFile();
            }

            return;
        }

        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move($this->getUploadDir(), $this->path);
        $filePath = $this->getUploadDir().'/'.$this->temp;

        $this->setFileInfoFromFile();

        // check if we have an old asset
        if (isset($this->temp) && file_exists($filePath)) {
            // delete the old asset
            unlink($filePath);
            // clear the temp asset path
            $this->temp = null;
        }

        // Remove temporary folder and files
        $fs = new Filesystem();
        $fs->remove($this->getAbsoluteTempDir());

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }

    /**
     * Remove a file.
     */
    public function setFileInfoFromFile(): void
    {
        // get some basic information about the file type
        $fileInfo = $this->getFileInfo();

        if (!is_array($fileInfo)) {
            return;
        }

        // set the mime and extension column values
        $this->setExtension($fileInfo['extension']);
        $this->setMime($fileInfo['mime']);
        $this->setSize($fileInfo['size']);
    }

    /**
     * Remove a file.
     *
     * @param bool $temp >> regular uploaded file or temporary
     */
    public function removeUpload($temp = false): void
    {
        if ($temp) {
            $file = $this->getAbsoluteTempPath();
        } else {
            $file = $this->getAbsolutePath();
        }

        if ($file && file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Returns absolute path to the file.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    /**
     * Returns absolute path to temporary file.
     *
     * @return string
     */
    public function getAbsoluteTempPath()
    {
        return null === $this->tempId || null === $this->tempName
            ? null
            : $this->getAbsoluteTempDir().'/'.$this->tempName;
    }

    /**
     * Returns absolute path to temporary file.
     *
     * @return string
     */
    public function getAbsoluteTempDir()
    {
        return null === $this->tempId
            ? null
            : $this->getUploadDir().'/tmp/'.$this->tempId;
    }

    /**
     * Returns absolute path to upload dir.
     *
     * @return string
     */
    protected function getUploadDir()
    {
        if ($this->uploadDir) {
            return $this->uploadDir;
        }

        return 'media/files';
    }

    /**
     * Set uploadDir.
     *
     * @param string $uploadDir
     *
     * @return Asset
     */
    public function setUploadDir($uploadDir)
    {
        $this->uploadDir = $uploadDir;

        return $this;
    }

    /**
     * Returns maximal uploadable size in bytes.
     * If not set, 6000000 is default.
     *
     * @return string
     */
    protected function getMaxSize()
    {
        if ($this->maxSize) {
            return $this->maxSize;
        }

        return 6_000_000;
    }

    /**
     * Set max size.
     *
     * @param string $maxSize
     *
     * @return Asset
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * Returns file extension.
     *
     * @return string
     */
    public function getFileType()
    {
        if (!empty($this->extension) && empty($this->changes['originalFileName'])) {
            return $this->extension;
        }

        if ($this->isRemote()) {
            return pathinfo(parse_url($this->getRemotePath(), PHP_URL_PATH), PATHINFO_EXTENSION);
        }

        if (null === $this->loadFile()) {
            return '';
        }

        return $this->loadFile()->guessExtension();
    }

    /**
     * Returns some file info.
     *
     * @return array
     */
    public function getFileInfo()
    {
        $fileInfo = [];

        if ($this->isRemote()) {
            $ch = curl_init($this->getRemotePath());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_exec($ch);

            // build an array of handy info
            $fileInfo['mime']      = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $fileInfo['extension'] = $this->getFileType();
            $fileInfo['size']      = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            return $fileInfo;
        }

        if (null === $this->loadFile()) {
            return '';
        }

        // return an array of file type info
        $fileInfo['mime']      = $this->loadFile()->getMimeType();
        $fileInfo['extension'] = $this->getFileType();
        $fileInfo['size']      = $this->getSize(false, true);

        return $fileInfo;
    }

    /**
     * Returns file mime type.
     *
     * @return string
     */
    public function getFileMimeType()
    {
        if ($this->isRemote()) {
            $ch = curl_init($this->getRemotePath());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);

            return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        }

        if (null === $this->loadFile()) {
            return '';
        }

        return $this->loadFile()->getMimeType();
    }

    /**
     * Returns icon class based on file type.
     */
    public function getIconClass(): string
    {
        $fileType = $this->getFileType();

        // return missing file icon if file type is empty
        if (!$fileType) {
            return 'ri-prohibited-line';
        }

        $fileTypes = $this->getFileExtensions();

        // Search for icon name by file extension.
        foreach ($fileTypes as $icon => $extensions) {
            if (in_array($fileType, $extensions)) {
                return 'ri-file-'.$icon.'-line';
            }
        }

        // File extension is unknown, display general file icon.
        return 'ri-file-line';
    }

    /**
     * Decides if an asset is image displayable by browser.
     */
    public function isImage(): bool
    {
        $fileType = strtolower($this->getFileType());

        if (!$fileType) {
            return false;
        }

        $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $imageTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Returns array of common extensions.
     *
     * @return array<string, string[]>
     */
    public function getFileExtensions(): array
    {
        return [
            'excel' => [
                'xlsx',
                'xlsm',
                'xlsb',
                'xltx',
                'xltm',
                'xls',
                'xlt',
            ],
            'word' => [
                'doc',
                'docx',
                'docm',
                'dotx',
            ],
            'pdf' => [
                'pdf',
            ],
            'audio' => [
                'mp3',
            ],
            'zip' => [
                'zip',
                'rar',
                'iso',
                'tar',
                'gz',
                '7z',
            ],
            'image' => [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'ico',
                'bmp',
                'psd',
            ],
            'text' => [
                'txt',
                'pub',
            ],
            'code' => [
                'php',
                'js',
                'json',
                'yaml',
                'xml',
                'html',
                'htm',
                'sql',
            ],
            'ppt' => [
                'ppt',
                'pptx',
                'pptm',
                'xps',
                'potm',
                'potx',
                'pot',
                'pps',
                'odp',
            ],
            'video' => [
                'wmv',
                'avi',
                'mp4',
                'mkv',
                'mpeg',
            ],
        ];
    }

    /**
     * Load the file object from it's path.
     *
     * @return File|null
     */
    public function loadFile($temp = false)
    {
        if ($temp) {
            $path = $this->getAbsoluteTempPath();
        } else {
            $path = $this->getAbsolutePath();
        }

        if (!$path || !file_exists($path)) {
            return null;
        }

        try {
            $file = new File($path);
        } catch (FileNotFoundException) {
            $file = null;
        }

        return $file;
    }

    /**
     * Load content of the file from it's path.
     */
    public function getFileContents(): string|bool
    {
        $path = $this->getFilePath();

        return file_get_contents($path);
    }

    /**
     * Get the path to the file; a URL if remote or full file path if local.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->isRemote() ? $this->getRemotePath() : $this->getAbsolutePath();
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        // Add a constraint to manage the file upload data
        $metadata->addConstraint(new Assert\Callback([self::class, 'validateFile']));
    }

    /**
     * Validator to ensure proper data for the file fields.
     *
     * @param Asset                     $object  Entity object to validate
     * @param ExecutionContextInterface $context Context object
     */
    public static function validateFile($object, ExecutionContextInterface $context): void
    {
        if ($object->isLocal()) {
            $tempName = $object->getTempName();
            $path     = $object->getPath();

            // If the object is stored locally, we should have file data
            if ($object->isNew() && null === $tempName && null === $path) {
                $context->buildViolation('mautic.asset.asset.error.missing.file')
                    ->atPath('tempName')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            if (null === $object->getTitle()) {
                $context->buildViolation('mautic.asset.asset.error.missing.title')
                    ->atPath('title')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }
            $parameters        = (new ParameterLoader())->getParameterBag();
            $extensionsAllowed = $parameters->get('allowed_extensions');
            $mimeTypesMap      = $parameters->get('allowed_mimetypes');
            $mimeTypesAllowed  = array_intersect_key($mimeTypesMap, array_flip($extensionsAllowed));

            $fileMimeType        = $object->getFileMimeType();
            $fileExtension       = strtolower($object->getExtension() ?? '');
            $lowercaseMimeTypes  = array_change_key_case($mimeTypesAllowed, CASE_LOWER);
            $lowercaseMimeValues = array_map('strtolower', $mimeTypesAllowed);

            if (!empty($fileMimeType) && array_key_exists($fileExtension, $lowercaseMimeTypes) && !in_array(strtolower($fileMimeType), $lowercaseMimeValues, true)) {
                $context->buildViolation('mautic.asset.asset.error.invalid.mimetype', [
                    '%fileMimetype%'=> $object->getFileMimeType(),
                    '%mimetypes%'   => implode(', ', $mimeTypesAllowed),
                ])->atPath('file')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            $fileType = $object->getExtension();
            if (null !== $fileType && !in_array(strtolower($fileType), array_map('strtolower', $extensionsAllowed), true)) {
                $context->buildViolation('mautic.asset.asset.error.file.extension', [
                    '%fileExtension%'=> $object->getExtension(),
                    '%extensions%'   => implode(', ', $extensionsAllowed),
                ])->atPath('file')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            // Unset any remote file data
            $object->setRemotePath(null);
        } elseif ($object->isRemote()) {
            // If the object is stored remotely, we should have a remote path
            if (null === $object->getRemotePath()) {
                $context->buildViolation('mautic.asset.asset.error.missing.remote.path')
                    ->atPath('remotePath')
                    ->setTranslationDomain('validators')
                    ->addViolation();
            }

            // Unset any local file data
            $object->setPath(null);
        }
    }

    /**
     * Set temporary ID.
     *
     * @param string $tempId
     *
     * @return Asset
     */
    public function setTempId($tempId)
    {
        $this->tempId = $tempId;

        return $this;
    }

    /**
     * Get temporary ID.
     *
     * @return string
     */
    public function getTempId()
    {
        return $this->tempId;
    }

    /**
     * Set temporary file name.
     *
     * @param string $tempName
     *
     * @return Asset
     */
    public function setTempName($tempName)
    {
        $this->tempName = $tempName;

        return $this;
    }

    /**
     * Get temporary file name.
     *
     * @return string
     */
    public function getTempName()
    {
        return $this->tempName;
    }

    /**
     * @param bool   $humanReadable
     * @param bool   $forceUpdate
     * @param string $inUnit
     *
     * @return float|string
     */
    public function getSize($humanReadable = true, $forceUpdate = false, $inUnit = '')
    {
        if (empty($this->size) || $forceUpdate) {
            // Try to fetch it
            if ($this->isRemote()) {
                $ch = curl_init($this->getRemotePath());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

                curl_exec($ch);

                $this->setSize(round(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD)));
            }

            if (null === $this->loadFile()) {
                return 0;
            }

            $this->setSize(round($this->loadFile()->getSize()));
        }

        return ($humanReadable) ? static::convertBytesToHumanReadable($this->size, $inUnit) : $this->size;
    }

    /**
     * @param mixed $size
     *
     * @return Asset
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get value from PHP configuration with special handling of -1.
     *
     * @param string    $setting
     * @param bool|true $convertToBytes
     */
    public static function getIniValue($setting, $convertToBytes = true): int
    {
        $value = ini_get($setting);

        if (-1 == $value || 0 === $value) {
            return PHP_INT_MAX;
        }

        if ($convertToBytes) {
            $value = FileHelper::convertPHPSizeToBytes($value);
        }

        return (int) $value;
    }

    /**
     * @param string $unit
     */
    public static function convertBytesToHumanReadable($size, $unit = ''): string
    {
        [$number, $unit] = self::convertBytesToUnit($size, $unit);

        // Format number
        $number = number_format($number, 2);

        // Remove trailing .00
        $number = str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;

        return $number.' '.$unit;
    }

    /**
     * @param string $unit
     */
    public static function convertBytesToUnit($size, $unit = ''): array
    {
        $unit = strtoupper($unit);

        if ((!$unit && $size >= 1 << 30) || 'GB' == $unit || 'G' == $unit) {
            return [$size / (1 << 30), 'GB'];
        }
        if ((!$unit && $size >= 1 << 20) || 'MB' == $unit || 'M' == $unit) {
            return [$size / (1 << 20), 'MB'];
        }
        if ((!$unit && $size >= 1 << 10) || 'KB' == $unit || 'K' == $unit) {
            return [$size / (1 << 10), 'KB'];
        }

        // Add zero to remove useless .00
        return [$size, 'bytes'];
    }

    /**
     * @return string|null
     */
    public function getDownloadUrl()
    {
        return $this->downloadUrl;
    }

    /**
     * @param string|null $downloadUrl
     *
     * @return Asset
     */
    public function setDownloadUrl($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;

        return $this;
    }

    public function isLocal(): bool
    {
        return 'local' === $this->storageLocation;
    }

    public function isRemote(): bool
    {
        return 'remote' === $this->storageLocation;
    }

    /**
     * @return bool
     */
    public function getDisallow()
    {
        return $this->disallow;
    }

    /**
     * @param mixed $disallow
     */
    public function setDisallow($disallow): void
    {
        $this->disallow = $disallow;
    }
}
