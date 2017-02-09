<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\Form\Form;

trait FormThemeTrait
{
    /**
     * Sets a specific theme for the form.
     *
     * @param Form   $form
     * @param string $template
     * @param mixed  $themes
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function setFormTheme(Form $form, $template, $themes = null)
    {
        $formView = $form->createView();

        $templating = $this->container->get('mautic.helper.templating')->getTemplating();
        if ($templating instanceof DelegatingEngine) {
            $templating = $templating->getEngine($template);
        }

        // Extract form theme from options if applicable
        $fieldThemes = [];
        $findThemes  = function ($form, $formView) use ($templating, &$findThemes, &$fieldThemes) {
            /** @var Form $field */
            foreach ($form as $name => $field) {
                $fieldView = $formView[$name];
                if ($theme = $field->getConfig()->getOption('default_theme')) {
                    $fieldThemes[] = $theme;
                    $templating->get('form')->setTheme($fieldView, $theme);
                }

                if ($field->count()) {
                    $findThemes($field, $fieldView);
                }
            }
        };

        $findThemes($form, $formView);

        $themes   = (array) $themes;
        $themes[] = 'MauticCoreBundle:FormTheme\Custom';
        $themes   = array_values(array_unique(array_merge($themes, $fieldThemes)));

        $templating->get('form')->setTheme($formView, $themes);

        return $formView;
    }
}
