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
use Mautic\CategoryBundle\Entity\Category;
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
#[Upload]
class Asset extends FormEntity implements UuidInterface
{
    use UuidTrait;

    use ProjectTrait;

    public const ENTITY_NAME = 'asset';

    #[Groups(['asset:read', 'download:read', 'email:read'])]
    private ?int $id = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $title = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $description = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private string $storageLocation = 'local';

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $path = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    #[Sequentially([
        new Assert\Url(message: 'mautic.asset.validation.error.url'),
        new SafeRemoteUrl(),
    ])]
    private ?string $remotePath = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $originalFileName = null;

    private ?File $file = null;

    /**
     * Holds upload directory.
     */
    private ?string $uploadDir = null;

    /**
     * Holds max size of uploaded file.
     */
    private int|float|null $maxSize = null;

    /**
     * Temporary location when asset file is beeing updated.
     * We need to keep the old file till we are sure the new
     * one is stored correctly.
     */
    private ?string $temp = null;

    /**
     * Temporary ID used for file upload and validations
     * before the actual ID is known.
     */
    private ?string $tempId = null;

    /**
     * Temporary file name used for file upload and validations
     * before the actual ID is known.
     */
    private ?string $tempName = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $alias = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private string $language = 'en';

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?\DateTimeInterface $publishUp = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?\DateTimeInterface $publishDown = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private int $downloadCount = 0;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private int $uniqueDownloadCount = 0;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private int $revision = 1;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?Category $category = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $extension = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $mime = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?int $size = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private ?string $downloadUrl = null;

    #[Groups(['asset:read', 'asset:write', 'download:read', 'email:read'])]
    private bool $disallow = true;

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

        $builder->createField('alias', Types::STRING)
            ->columnName('alias')
            ->nullable()
            ->build();

        $builder->createField('storageLocation', Types::STRING)
            ->columnName('storage_location')
            ->nullable()
            ->build();

        $builder->createField('path', Types::STRING)
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

        $builder->createField('language', Types::STRING)
            ->columnName('lang')
            ->build();

        $builder->addPublishDates();

        $builder->createField('downloadCount', Types::INTEGER)
            ->columnName('download_count')
            ->build();

        $builder->createField('uniqueDownloadCount', Types::INTEGER)
            ->columnName('unique_download_count')
            ->build();

        $builder->addField('revision', Types::INTEGER);

        $builder->addCategory();

        $builder->createField('extension', Types::STRING)
            ->nullable()
            ->build();

        $builder->createField('mime', Types::STRING)
            ->nullable()
            ->build();

        $builder->createField('size', Types::INTEGER)
            ->nullable()
            ->build();

        $builder->createField('disallow', Types::BOOLEAN)
            ->nullable()
            ->build();

        static::addUuidField($builder);
        self::addProjectsField($builder, 'asset_projects_xref', 'asset_id');
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

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        // check if we have an old asset path
        if (null !== $this->path) {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        }
    }

    public function getFile(): ?File
    {
        // if file is not set, try to find it at temp folder
        if ($this->isLocal() && !$this->file instanceof File) {
            $tempFile = $this->loadFile(true);

            if ($tempFile) {
                $this->setFile($tempFile);
            }
        }

        return $this->file;
    }

    public function setTitle(?string $title): static
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): void
    {
        $this->mime = $mime;
    }

    public function setOriginalFileName(?string $originalFileName): static
    {
        $this->isChanged('originalFileName', $originalFileName);
        $this->originalFileName = $originalFileName;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setStorageLocation(string $storageLocation): static
    {
        $this->isChanged('storageLocation', $storageLocation);
        $this->storageLocation = $storageLocation;

        return $this;
    }

    public function getStorageLocation(): string
    {
        if (null === $this->storageLocation) {
            $this->storageLocation = 'local';
        }

        return $this->storageLocation;
    }

    public function setPath(?string $path): self
    {
        $this->isChanged('path', $path);
        $this->path = $path;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setRemotePath(?string $remotePath): self
    {
        $this->isChanged('remotePath', $remotePath);
        $this->remotePath = $remotePath;

        return $this;
    }

    public function getRemotePath(): ?string
    {
        return $this->remotePath;
    }

    public function setAlias(?string $alias): self
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setPublishUp(?\DateTimeInterface $publishUp): static
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    public function getPublishUp(): ?\DateTimeInterface
    {
        return $this->publishUp;
    }

    public function setPublishDown(?\DateTimeInterface $publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    public function getPublishDown(): ?\DateTimeInterface
    {
        return $this->publishDown;
    }

    public function setDownloadCount(int $downloadCount): static
    {
        $this->downloadCount = $downloadCount;

        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function setRevision(int $revision): static
    {
        $this->revision = $revision;

        return $this;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function setLanguage(string $language): static
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setCategory(?Category $category = null): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setUniqueDownloadCount(int $uniqueDownloadCount): static
    {
        $this->uniqueDownloadCount = $uniqueDownloadCount;

        return $this;
    }

    public function getUniqueDownloadCount(): int
    {
        return $this->uniqueDownloadCount;
    }

    public function setFileNameFromRemote(): void
    {
        $fileName = basename($this->remotePath);

        $this->setOriginalFileName($fileName);

        // set the asset title as original file name if title is missing
        if (null === $this->title) {
            $this->setTitle($fileName);
        }
    }

    public function preUpload(): void
    {
        if (null !== $this->getFile()) {
            // set the asset title as original file name if title is missing
            if (null === $this->title) {
                $this->setTitle($this->file->getClientOriginalName());
            }

            $filename  = sha1(uniqid(mt_rand(), true));
            $extension = $this->getFile()->guessExtension();

            if (empty($extension)) {
                // get it from the original name
                $extension = pathinfo($this->originalFileName, PATHINFO_EXTENSION);
            }
            $this->path = $filename.'.'.$extension;
        } elseif ($this->isRemote() && null !== $this->remotePath) {
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
        if (null !== $this->temp && file_exists($filePath)) {
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
     * @param bool $temp Whether the file is a regular uploaded file or temporary
     */
    public function removeUpload(bool $temp = false): void
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
     */
    public function getAbsolutePath(): ?string
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    /**
     * Returns absolute path to temporary file.
     */
    public function getAbsoluteTempPath(): ?string
    {
        return null === $this->tempId || null === $this->tempName
            ? null
            : $this->getAbsoluteTempDir().'/'.$this->tempName;
    }

    /**
     * Returns absolute path to temporary file.
     */
    public function getAbsoluteTempDir(): ?string
    {
        return null === $this->tempId
            ? null
            : $this->getUploadDir().'/tmp/'.$this->tempId;
    }

    /**
     * Returns absolute path to upload dir.
     */
    protected function getUploadDir(): string
    {
        if ($this->uploadDir) {
            return $this->uploadDir;
        }

        return 'media/files';
    }

    public function setUploadDir(?string $uploadDir): static
    {
        $this->uploadDir = $uploadDir;

        return $this;
    }

    /**
     * Returns maximal uploadable size in bytes.
     * If not set, 6000000 is default.
     */
    protected function getMaxSize(): int|float
    {
        if ($this->maxSize) {
            return $this->maxSize;
        }

        return 6_000_000;
    }

    public function setMaxSize(int|float|null $maxSize): static
    {
        $this->maxSize = $maxSize;

        return $this;
    }

    /**
     * Returns file extension.
     */
    public function getFileType(): ?string
    {
        if (!empty($this->extension) && empty($this->changes['originalFileName'])) {
            return $this->extension;
        }

        if ($this->isRemote()) {
            return pathinfo(parse_url($this->remotePath, PHP_URL_PATH), PATHINFO_EXTENSION);
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
    public function getFileInfo(): array|string
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
    public function loadFile(bool $temp = false): ?File
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
     */
    public function getFilePath(): ?string
    {
        return $this->isRemote() ? $this->remotePath : $this->getAbsolutePath();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setTempId(?string $tempId): static
    {
        $this->tempId = $tempId;

        return $this;
    }

    public function getTempId(): ?string
    {
        return $this->tempId;
    }

    public function setTempName(?string $tempName): static
    {
        $this->tempName = $tempName;

        return $this;
    }

    public function getTempName(): ?string
    {
        return $this->tempName;
    }

    public function getSize(bool $humanReadable = true, bool $forceUpdate = false, $inUnit = ''): string|int|null
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

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get value from PHP configuration with special handling of -1.
     */
    public static function getIniValue(string $setting, bool $convertToBytes = true): int
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

    public static function convertBytesToHumanReadable(int|float $size, string $unit = ''): string
    {
        [$number, $unit] = self::convertBytesToUnit($size, $unit);

        // Format number
        $number = number_format($number, 2);

        // Remove trailing .00
        $number = str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;

        return $number.' '.$unit;
    }

    public static function convertBytesToUnit(int|float $size, string $unit = ''): array
    {
        $unit = strtoupper($unit);

        if ((!$unit && $size >= 1 << 30) || 'GB' === $unit || 'G' === $unit) {
            return [$size / (1 << 30), 'GB'];
        }
        if ((!$unit && $size >= 1 << 20) || 'MB' === $unit || 'M' === $unit) {
            return [$size / (1 << 20), 'MB'];
        }
        if ((!$unit && $size >= 1 << 10) || 'KB' === $unit || 'K' === $unit) {
            return [$size / (1 << 10), 'KB'];
        }

        // Add zero to remove useless .00
        return [$size, 'bytes'];
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): static
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

    public function getDisallow(): bool
    {
        return $this->disallow;
    }

    public function setDisallow(bool $disallow): void
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

        $mimeType = (string) new \finfo(FILEINFO_MIME_TYPE)->buffer($chunk);

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
