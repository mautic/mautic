<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

/**
 * Class PointActionHelper
 */
class PointActionHelper
{

    /**
     * @param $passthrough
     * @param $action
     *
     * @return int
     */
    public static function onFormSubmit($passthrough, $action)
    {
        $form         = $passthrough->getForm();
        $formId       = $form->getId();
        $limitToForms = $action['properties']['forms'];

        if (!empty($limitToForms) && !in_array($formId, $limitToForms)) {
            //no points change
            return 0;
        }

        return $action['properties']['delta'];
    }
}
