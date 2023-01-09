<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\TemplatingHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Error\Error;

trait FormThemeTrait
{
    /**
     * Sets a specific theme for the form.
     *
     * @param FormInterface $form
     * @param string $template
     * @param mixed $themes
     *
     * @return FormView
     * @throws Error
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setFormTheme(FormInterface $form, $template, $themes = null)
    {
        $formView = $form->createView();
        $templating = $this->container->get('mautic.helper.templating');
        $templating = $templating->getTemplating();

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
        /** @var TwigEngine $templating */
        //$templating->get('form')->setTheme($formView, $themes);
        $templating->render('MauticEmailBundle:Email:form.html.twig', [$themes]);
        return $formView;
    }
}
