<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;

/**
 * Class MautibotHelper.
 */
class MautibotHelper extends Helper
{
    /**
     * @param $image openMouth | smile | wave
     *
     * @return string
     */
    public function getImage($image)
    {
        return (isset(static::$$image)) ? static::$$image : '';
    }

    /**
     * Static alternative to retrieve image.
     *
     * @param $image openMouth | smile | wave
     *
     * @return string
     */
    public static function get($image)
    {
        return (isset(static::$$image)) ? static::$$image : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mautibot';
    }
}
