<?php
/*
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Uploader\Locator;

use Mautic\CoreBundle\Uploader\AbstractUploader;

class DirectoryLocator
{
    private $decoratorFactory;

    /**
     * PathDecorator constructor.
     */
    public function __construct(AbstractUploader $decoratorFactory)
    {
        $this->decoratorFactory     = $decoratorFactory;
        $this->pathsHelper          = $this->decoratorFactory->getPathsHelper();
        $this->coreParametersHelper = $this->decoratorFactory->getCoreParametersHelper();
    }

    /**
     * @param bool $fullPath
     *
     * @return string
     */
    public function getUploadPathDirectory()
    {
        $directories = $this->decoratorFactory->getUploadDirectory();
        array_unshift($directories, $this->pathsHelper->getSystemPath($this->decoratorFactory->getSystemPathDirectory(), true));

        return implode(DIRECTORY_SEPARATOR, $directories);
    }

    /**
     * @return string
     */
    public function getUploadUrlDirectory()
    {
        $directories = $this->decoratorFactory->getUploadDirectory();
        array_unshift($directories, $this->pathsHelper->getSystemPath($this->decoratorFactory->getSystemPathDirectory()));
        array_unshift($directories, $this->coreParametersHelper->getParameter('site_url'));

        return implode('/', $directories);
    }
}
