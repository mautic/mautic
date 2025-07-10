<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

trait FormThemeTrait
{
    /**
     * Sets a specific theme for the form.
     *
     * @param FormInterface<mixed> $form
     * @param mixed                $themes
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function setFormTheme(FormInterface $form, Environment $twig, $themes = null)
    {
        $formView = $form->createView();

        // Extract form theme from options if applicable
        $fieldThemes = [];
        $findThemes  = function ($form, $formView) use ($twig, &$findThemes, &$fieldThemes): void {
            /** @var Form $field */
            foreach ($form as $name => $field) {
                $fieldView = $formView[$name];
                if ($theme = $field->getConfig()->getOption('default_theme')) {
                    $fieldThemes[] = $theme;
                    $twig->get('form')->setTheme($fieldView, $theme);
                }

                if ($field->count()) {
                    $findThemes($field, $fieldView);
                }
            }
        };

        $findThemes($form, $formView);

        $themes = (array) $themes;
        $themes = array_values(array_unique(array_merge($themes, $fieldThemes)));

        $twig->get('form')->setTheme($formView, $themes);

        return $formView;
    }
}
