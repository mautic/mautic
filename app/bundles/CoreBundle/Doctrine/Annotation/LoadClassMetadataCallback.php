<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Annotation;

/**
 * Class LoadClassMetadataCallback
 *
 * @Annotation
 */
class LoadClassMetadataCallback
{

    /**
     * @var string
     */
    public $functionName;

    /**
     * @param string $functionName
     */
    public function __construct($functionName)
    {
        $this->functionName = $functionName;
    }
}
