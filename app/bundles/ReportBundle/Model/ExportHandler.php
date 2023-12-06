<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\ReportBundle\Exception\FileIOException;

class ExportHandler
{
    /**
     * @var string
     */
    private $dir;

    private \Mautic\CoreBundle\Helper\FilePathResolver $filePathResolver;

    public function __construct(CoreParametersHelper $coreParametersHelper, FilePathResolver $filePathResolver)
    {
        $this->dir              = $coreParametersHelper->get('report_temp_dir');
        $this->filePathResolver = $filePathResolver;
    }

    /**
     * @return bool|resource
     *
     * @throws FileIOException
     */
    public function getHandler($fileName)
    {
        $path = $this->getPath($fileName);

        if (false === ($handler = @fopen($path, 'a'))) {
            throw new FileIOException('Could not open file '.$path);
        }

        return $handler;
    }

    /**
     * @param resource $handler
     */
    public function closeHandler($handler): void
    {
        fclose($handler);
    }

    /**
     * @param string $fileName
     */
    public function removeFile($fileName): void
    {
        try {
            $path = $this->getPath($fileName);
            $this->filePathResolver->delete($path);
        } catch (FileIOException $e) {
        }
    }

    /**
     * @throws FileIOException
     */
    public function getPath($fileName): string
    {
        try {
            $this->filePathResolver->createDirectory($this->dir);
        } catch (FilePathException $e) {
            throw new FileIOException('Could not create directory '.$this->dir, 0, $e);
        }

        return $this->dir.'/'.$fileName.'.csv';
    }
}
