<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class ReportHelper.
 */
class ReportHelper extends Helper
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'report';
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function getReportBuilderFieldType($type)
    {
        switch ($type) {
            case 'number':
                $type = 'int';
                break;
            case 'lookup':
            case 'text':
            case 'url':
            case 'email':
            case 'tel':
            case 'region':
            case 'country':
            case 'locale':
                $type = 'string';
                break;
        }

        return $type;
    }
}
