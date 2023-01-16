<?php

namespace Mautic\CoreBundle\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

trait FormThemeTrait
{
    /**
     * Sets a specific theme for the form.
     *
     * @param FormInterface<object> $form
     * @param mixed                 $themes
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setFormTheme(FormInterface $form, string $template, $themes = null): FormView
    {
        $formView = $form->createView();

        $templating = $this->container->get('mautic.helper.templating')->getTemplating();
        if ($templating instanceof DelegatingEngine) {
            $templating = $templating->getEngine($template);
        }

        // Extract form theme from options if applicable
        $fieldThemes = [];
        $findThemes  = function (FormInterface $form, FormView $formView) use ($templating, &$findThemes, &$fieldThemes) {
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
