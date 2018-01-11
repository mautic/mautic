<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CompanyFiles.
 */
class CompanyFiles
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \Mautic\LeadBundle\Entity\Company
     */
    private $company;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $originalFileName;

    /**
     * @var string
     */
    private $mime;

    /**
     * @var int
     */
    private $size;

    /**
     * Holds upload directory.
     */
    private $uploadDir;

    /**
     * Holds all file.
     */
    private $file;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('company_files')
                ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\CompanyFilesRepository');

        $builder->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createManyToOne('company', 'Company')
            ->addJoinColumn('company_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('title', 'string')
            ->nullable()
            ->build();

        $builder->createField('path', 'string')
            ->nullable()
            ->build();

        $builder->createField('originalFileName', 'string')
            ->columnName('original_file_name')
            ->nullable()
            ->build();

        $builder->createField('mime', 'string')
            ->nullable()
            ->build();

        $builder->createField('size', 'string')
            ->nullable()
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title = null)
    {
        $this->title = $title;
    }

    /**
     * @return Path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     *
     * @return CompanyFiles
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return originalFileName
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * @param $originalFileName
     */
    public function setOriginalFileName($originalFileName)
    {
        $this->originalFileName = $originalFileName;
    }

    /**
     * @return mime
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * @param $mime
     */
    public function setMime($mime)
    {
        $this->mime = $mime;
    }

    /**
     * @return size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param Company $size
     */
    public function setSize($size)
    {
        $this->size = $size;
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

        return 'media/companies';
    }

    /**
     * Set uploadDir.
     *
     * @param string $uploadDir
     *
     * @return CompanyFiles
     */
    public function setUploadDir($uploadDir)
    {
        $this->uploadDir = $uploadDir;

        return $this;
    }

    /**
     * @param UploadedFile $file
     *
     * @return CompanyFiles
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return CompanyFiles
     */
    public function upload()
    {
        $this->setFileInfoFromFile();

        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move($this->getUploadDir(), $this->getPath());

        return $this;
    }

    /**
     * set file info from file.
     */
    public function setFileInfoFromFile()
    {
        $this->setOriginalFileName($this->getFile()->getClientOriginalName());
        $this->setMime($this->getFile()->getClientMimeType());
        $this->setSize($this->getFile()->getClientSize());
        $this->setTitle(str_replace('.'.$this->getFile()->getClientOriginalExtension(), '', $this->getFile()->getClientOriginalName()));
        $this->setPath(md5($this->getFile()->getClientOriginalName().time()).'.'.$this->getFile()->getClientOriginalExtension());
    }

    /**
     * Remove file.
     */
    public function removeCompanyFile()
    {
        $file = $this->getUploadDir().'/'.$this->getPath();
        if ($file && file_exists($file)) {
            unlink($file);
        }
    }
}
