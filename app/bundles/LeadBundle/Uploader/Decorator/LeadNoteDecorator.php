<?php
/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 *  * @copyright   2019 Mautic Contributors. All rights reserved
 *  * @author      Mautic
 *  *
 *
 *  * @see        http://mautic.org
 *  *
 *
 *  * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MauticLeadBundle\Uploader\Decorator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadNoteDecorator
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /** @var \Symfony\Component\HttpFoundation\Request */
    private $request;

    /**
     * UploadDecoratorPath constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param PathsHelper          $pathsHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, PathsHelper $pathsHelper, RequestStack $requestStack)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->pathsHelper          = $pathsHelper;
        $this->request              = $requestStack->getCurrentRequest();
    }

    /**
     * @param bool $fullPath
     *
     * @return string
     */
    public function getUploadPathDirectory()
    {
        return $this->pathsHelper->getSystemPath(
            'files'
        ).DIRECTORY_SEPARATOR;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
