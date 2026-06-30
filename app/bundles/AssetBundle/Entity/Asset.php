<?php

namespace Mautic\AssetBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\AssetBundle\Validator\Constraints\Upload;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Validator\SafeRemoteUrl;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('asset:assets:viewown')"),
        new Post(security: "is_granted('asset:assets:create')"),
        new Get(security: "is_granted('asset:assets:viewown', object)"),
        new Put(security: "is_granted('asset:assets:editown', object)"),
        new Patch(security: "is_granted('asset:assets:editother', object)"),
        new Delete(security: "is_granted('asset:assets:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['asset:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['asset:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Asset extends FormEntity implements UuidInterface
{
    use UuidTrait;

    use ProjectTrait;

    public const ENTITY_NAME = 'asset';

    /**
     * @var int|null
     */
    #[Groups(['asset:read', 'download:read', 'email:read'])]
    private $id;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $title;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $description;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $storageLocation = 'local';

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $path;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $remotePath;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $originalFileName;

    /**
     * @var File|null
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
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $alias;

    /**
     * @var string
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $language = 'en';

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $downloadCount = 0;

    /**
     * @var int
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $uniqueDownloadCount = 0;

    /**
     * @var int
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $revision = 1;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     **/
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $category;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $extension;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $mime;

    /**
     * @var int|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $size;

    /**
     * @var string|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $downloadUrl;

    /**
     * @var bool|null
     */
    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private $disallow = true;

    public function __construct()
    {
        $this->initializeProjects();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('assets')
            ->setCustomRepositoryClass(AssetRepository::class)
            ->addIndex(['alias'], 'asset_alias_search');

        $builder->addIdColumns('title');

        $builder->createField('alias', 'string')
            ->columnName('alias')
            ->nullable()
            ->build();

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
        self::addProjectsField($builder, 'asset_projects_xref', 'asset_id');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Upload());
        $metadata->addPropertyConstraint('remotePath', new Sequentially([
            new Assert\Url(message: 'mautic.asset.validation.error.url'),
            new SafeRemoteUrl(),
        ]));
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

        self::addProjectsInLoadApiMetadata($metadata, 'asset');
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
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets file.
     */
    public function setFile(?File $file = null): void
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
     * @return File|null
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
     */
    public function setTitle($title): static
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string|null
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
     * @return string|null
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
     */
    public function setOriginalFileName($originalFileName): static
    {
        $this->isChanged('originalFileName', $originalFileName);
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * Get originalFileName.
     *
     * @return string|null
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * Set storage location.
     *
     * @param string $storageLocation
     */
    public function setStorageLocation($storageLocation): static
    {
        $this->isChanged('storageLocation', $storageLocation);
        $this->storageLocation = $storageLocation;

        return $this;
    }

    /**
     * Get storage location.
     *
     * @return string|null
     */
    public function getStorageLocation()
    {
        if (null === $this->storageLocation) {
            $this->storageLocation = 'local';
        }

        return $this->storageLocation;
    }

    /**
     * @param ?string $path
     */
    public function setPath($path): Asset
    {
        $this->isChanged('path', $path);
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return ?string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param ?string $remotePath
     */
    public function setRemotePath($remotePath): Asset
    {
        $this->isChanged('remotePath', $remotePath);
        $this->remotePath = $remotePath;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getRemotePath()
    {
        return $this->remotePath;
    }

    /**
     * Set alias.
     */
    public function setAlias(?string $alias): self
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     */
    public function setPublishUp($publishUp): static
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTimeInterface|null
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTimeInterface $publishDown
     */
    public function setPublishDown($publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTimeInterface|null
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * Set downloadCount.
     *
     * @param int $downloadCount
     */
    public function setDownloadCount($downloadCount): static
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
     */
    public function setRevision($revision): static
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
     */
    public function setLanguage($language): static
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
     */
    public function setCategory(?\Mautic\CategoryBundle\Entity\Category $category = null): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Mautic\CategoryBundle\Entity\Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set uniqueDownloadCount.
     *
     * @param int $uniqueDownloadCount
     */
    public function setUniqueDownloadCount($uniqueDownloadCount): static
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
     */
    public function setUploadDir($uploadDir): static
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
     */
    public function setMaxSize($maxSize): static
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
     * @return array<string, float|string|false|null>|string
     */
    public function getFileInfo()
    {
        $fileInfo = [];

        if ($this->isRemote()) {
            $ch = $this->buildRemoteCurl();
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
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

    public function getFileMimeType(): ?string
    {
        if ($this->isRemote()) {
            return $this->getRemoteMimeTypeFromHeader();
        }

        $file = $this->loadFile();

        if (null === $file) {
            return '';
        }

        return $file->getMimeType();
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

        return in_array($fileType, $imageTypes);
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
     */
    public function loadFile($temp = false): ?File
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
     * Load the content of the file from its path.
     */
    public function getFileContents(): string|bool
    {
        $path = $this->getFilePath();
        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf('Asset file not found at path: "%s"', $path));
        }

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
     * @return string|null
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

    /**
     * Set temporary ID.
     *
     * @param string $tempId
     */
    public function setTempId($tempId): static
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
     */
    public function setTempName($tempName): static
    {
        $this->tempName = $tempName;

        return $this;
    }

    /**
     * Get temporary file name.
     *
     * @return ?string
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
                $ch = $this->buildRemoteCurl();
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);

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
     */
    public function setSize($size): static
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

        if ('-1' === $value || '0' === $value) {
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
     */
    public function setDownloadUrl($downloadUrl): static
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
     * @return bool|null
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

    /**
     * Returns the public slug for this asset.
     *
     * Uses `{uuid}` as the canonical slug.
     * Falls back to `{id}:{alias}` for backward compatibility.
     *
     * @throws \LogicException if the asset has not been saved yet and has no ID
     */
    public function getSlug(): string
    {
        if (null === $this->id) {
            throw new \LogicException('This asset must be saved before it can be used in a URL.');
        }

        return $this->uuid ?: $this->id.':'.$this->alias;
    }

    public function getRemoteMimeTypeFromHeader(): string
    {
        if (!$this->remotePath) {
            return '';
        }

        $ch = $this->buildRemoteCurl();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            return '';
        }

        $contentTypes = explode(',', (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        $mimeType     = end($contentTypes);

        return $this->extractMimeType($mimeType);
    }

    public function getRemoteMimeTypeFromMagicBytes(): string
    {
        if (!$this->remotePath) {
            return '';
        }

        $ch = $this->buildRemoteCurl();
        curl_setopt($ch, CURLOPT_RANGE, '0-1023');

        $chunk = curl_exec($ch);

        if (false === $chunk) {
            return '';
        }

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($chunk);

        return $this->extractMimeType($mimeType);
    }

    private function extractMimeType(string $mimeType): string
    {
        return trim(explode(';', $mimeType)[0]);
    }

    private function buildRemoteCurl(): \CurlHandle
    {
        $ch = curl_init($this->remotePath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

        return $ch;
    }
}
