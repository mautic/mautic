<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CacheStorageHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class CacheStorageHelper
{
    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir . '/data';
        $this->fs = new Filesystem();
        $this->touchDir($this->cacheDir);
    }

    /**
     * Creates the directory if doesn't exist
     *
     * @param string $dir
     */
    public function touchDir($dir)
    {
        if (!$this->fs->exists($dir)) {
            $this->fs->mkdir($dir);
        }
    }

    /**
     * Writes/updates a file in app/cache/{env}/data directory
     *
     * @param  string $fileName
     * @param  array  $data
     */
    public function set($fileName, $data)
    {
        $filePath = $this->cacheDir . '/' . $fileName . '.php';

        if (is_writable($filePath)) {
            file_put_contents($filePath, json_encode($data));
        }
    }

    /**
     * Reads a file in app/cache/{env}/data/$filename
     *
     * @param  string $fileName
     */
    public function get($fileName)
    {
        $filePath = $this->cacheDir . '/' . $fileName . '.php';

        if ($this->fs->exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }
        
        return false;
    }
}