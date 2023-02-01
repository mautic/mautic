<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Templating\Engine\PhpEngine;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;

trait FormThemeTrait
{
    /**
     * Sets a specific theme for the form.
     *
     * @param FormInterface<FormInterface> $form
     * @param string                       $template
     * @param mixed                        $themes
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function setFormTheme(FormInterface $form, $template, $themes = null)
    {
        $formView = $form->createView();

        $templating = $this->container->get('mautic.helper.templating')->getTemplating();
        $helper     = null;

        if ($templating instanceof DelegatingEngine) {
            $templating = $templating->getEngine($template);
        }

        if ($templating instanceof PhpEngine) {
            $helper = $templating->get('form');
        } elseif ($templating instanceof TwigEngine) {
            $helper = $this->container->get('templating.helper.form');
        }

        // Extract form theme from options if applicable
        $fieldThemes = [];
        $findThemes  = function ($form, $formView) use ($helper, &$findThemes, &$fieldThemes) {
            /** @var Form $field */
            foreach ($form as $name => $field) {
                $fieldView = $formView[$name];
                if ($theme = $field->getConfig()->getOption('default_theme')) {
                    $fieldThemes[] = $theme;
                    $helper->setTheme($fieldView, $theme);
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

        $helper->setTheme($formView, $themes);

        return $formView;
    }
}
