<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @var FilePathResolver
     */
    private $filePathResolver;

    public function __construct(CoreParametersHelper $coreParametersHelper, FilePathResolver $filePathResolver)
    {
        $this->dir              = $coreParametersHelper->getParameter('report_temp_dir');
        $this->filePathResolver = $filePathResolver;
    }

    /**
     * @param $fileName
     *
     * @return bool|resource
     *
     * @throws FileIOException
     */
    public function getHandler($fileName)
    {
        $path = $this->getPath($fileName);

        if (($handler = @fopen($path, 'a')) === false) {
            throw new FileIOException('Could not open file '.$path);
        }

        return $handler;
    }

    /**
     * @param resource $handler
     */
    public function closeHandler($handler)
    {
        fclose($handler);
    }

    /**
     * @param string $fileName
     */
    public function removeFile($fileName)
    {
        try {
            $path = $this->getPath($fileName);
            $this->filePathResolver->delete($path);
        } catch (FileIOException $e) {
        }
    }

    /**
     * @param $fileName
     *
     * @return string
     *
     * @throws FileIOException
     */
    public function getPath($fileName)
    {
        try {
            $this->filePathResolver->createDirectory($this->dir);
        } catch (FilePathException $e) {
            throw new FileIOException('Could not create directory '.$this->dir, 0, $e);
        }

        return $this->dir.'/'.$fileName.'.csv';
    }
}
