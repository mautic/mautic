<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Exception;


class FeedNotFoundException extends \RuntimeException
{
    public function __construct($message = 'Could not find feed with given URL')
    {
        parent::__construct($message);
    }
}
