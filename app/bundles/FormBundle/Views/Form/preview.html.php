<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//Use an iframe so that Mautic styling does not affect the form
?>
<iframe src="<?php echo $view['router']->generate('mautic_form_action', array('objectAction' => 'preview', 'objectId' => $form->getId())); ?>" style="margin: 0; padding: 0; border: none; width: 100%; height: 100%;"></iframe>