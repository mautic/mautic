<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Symfony\Component\Form\FormView;

/**
 * Class FormHelper
 *
 * @package Mautic\CoreBundle\Templating\Helper
 */
class FormHelper extends \Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper
{

    /**
     * Render widget if it exists
     *
     * @param array|FormView $form
     * @param                $key
     *
     * @return string
     */
    public function widgetIfExists ($form, $key)
    {
        return (isset($form[$key])) ? $this->widget($form[$key]) : '';
    }

    /**
     * Render row if it exists
     *
     * @param array|FormView $form
     * @param                $key
     *
     * @return string
     */
    public function rowIfExists ($form, $key)
    {
        return (isset($form[$key])) ? $this->row($form[$key]) : '';
    }

    /**
     * Render label if it exists
     *
     * @param array|FormView $form
     * @param                $key
     *
     * @return string
     */
    public function labelIfExists ($form, $key)
    {
        return (isset($form[$key])) ? $this->label($form[$key]) : '';
    }

    /**
     * Checks to see if the form and its children has an error
     *
     * @param $form
     *
     * @return bool
     */
    public function containsErrors (FormView $form) {
        if (count($form->vars['errors'])) {
            return true;
        }
        foreach ($form->children as $child) {
            if (count($child->vars['errors'])) {
               return true;
            }

            if (count($child->children)) {
                $hasErrors = $this->containsErrors($child);
                if ($hasErrors) {
                    return true;
                }
            }
        }
        return false;
    }
}
