<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;

/**
 * Class SysinfoModel
 */
class SysinfoModel extends CommonModel
{
    protected $phpInfo;

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'config:sysinfo';
    }

    /**
	 * Method to get the PHP info
	 *
	 * @return string
	 */
	public function getPhpInfo()
	{
		if (!is_null($this->phpInfo))
		{
			return $this->phpInfo;
		}
		ob_start();
		date_default_timezone_set('UTC');
		phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
		$phpInfo = ob_get_contents();
		ob_end_clean();
		preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpInfo, $output);
		$output = preg_replace('#<table[^>]*>#', '<table class="table table-striped">', $output[1][0]);
		$output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
		$output = preg_replace('#<hr />#', '', $output);
		$output = str_replace('<div class="center">', '', $output);
		$output = preg_replace('#<tr class="h">(.*)<\/tr>#', '<thead><tr class="h">$1</tr></thead><tbody>', $output);
		$output = str_replace('</table>', '</tbody></table>', $output);
		$output = str_replace('</div>', '', $output);
		$this->phpInfo = $output;
		return $this->phpInfo;
	}
}
