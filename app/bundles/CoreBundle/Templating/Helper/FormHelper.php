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

use Symfony\Component\Form\FormView;

/**
 * Class FormHelper.
 */
class FormHelper extends \Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper
{
    /**
     * Render widget if it exists.
     *
     * @param       $form
     * @param       $key
     * @param null  $template
     * @param array $variables
     *
     * @return mixed|string
     */
    public function widgetIfExists($form, $key, $template = null, $variables = [])
    {
        $content = (isset($form[$key])) ? $this->widget($form[$key], $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Render row if it exists.
     *
     * @param       $form
     * @param       $key
     * @param null  $template
     * @param array $variables
     *
     * @return mixed|string
     */
    public function rowIfExists($form, $key, $template = null, $variables = [])
    {
        $content = (isset($form[$key])) ? $this->row($form[$key], $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Render label if it exists.
     *
     * @param       $form
     * @param       $key
     * @param null  $template
     * @param array $variables
     *
     * @return mixed|string
     */
    public function labelIfExists($form, $key, $template = null, $variables = [])
    {
        $content = (isset($form[$key])) ? $this->label($form[$key], null, $variables) : '';

        if ($content && !empty($template)) {
            $content = str_replace('{content}', $content, $template);
        }

        return $content;
    }

    /**
     * Checks to see if the form and its children has an error.
     *
     * @param FormView $form
     * @param array    $exluding
     *
     * @return bool
     */
    public function containsErrors(FormView $form, array $exluding = [])
    {
        if (count($form->vars['errors'])) {
            return true;
        }
        foreach ($form->children as $key => $child) {
            if (in_array($key, $exluding)) {
                continue;
            }

            if (isset($child->vars['errors']) && count($child->vars['errors'])) {
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
