<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

/**
 * Class FormController
 *
 * @package Mautic\CoreBundle\Controller
 */
class FormController extends CommonController {

    /**
     * Binds form data, checks validity, and determines cancel request
     *
     * @param Form    $form
     * @return int
     */
    protected function checkFormValidity(Form &$form) {
        //bind request to the form
        $form->handleRequest($this->request);

        //redirect if the cancel button was clicked
        if ($form->has('cancel') && $form->get('cancel')->isClicked()) {
            return -1;
        } elseif ($form->isValid()) {
            return 1;
        } else {
            return 0;
        }
    }
}