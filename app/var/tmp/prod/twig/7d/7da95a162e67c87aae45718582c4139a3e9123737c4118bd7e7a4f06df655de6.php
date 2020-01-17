<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Source;
use Twig\Template;

/* TwigBundle:Exception:trace.html.twig */
class __TwigTemplate_9431c123892ca0218651a031f6b20cdb48edebc1c41d3ac97a72172aa3341f8d extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo '<div class="trace-line-header break-long-words ';
        echo (((twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', true, true, false, 1)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 1), false)) : (false))) ? ('sf-toggle') : ('');
        echo '" data-toggle-selector="#trace-html-';
        echo twig_escape_filter($this->env, ($context['prefix'] ?? null), 'html', null, true);
        echo '-';
        echo twig_escape_filter($this->env, ($context['i'] ?? null), 'html', null, true);
        echo '" data-toggle-initial="';
        echo (($context['_display_code_snippet'] ?? null)) ? ('display') : ('');
        echo '">
    ';
        // line 2
        if (((twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', true, true, false, 2)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 2), false)) : (false))) {
            // line 3
            echo '        <span class="icon icon-close">';
            echo twig_include($this->env, $context, '@Twig/images/icon-minus-square.svg');
            echo '</span>
        <span class="icon icon-open">';
            // line 4
            echo twig_include($this->env, $context, '@Twig/images/icon-plus-square.svg');
            echo '</span>
    ';
        }
        // line 6
        echo '
    ';
        // line 7
        if (twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'function', [], 'any', false, false, false, 7)) {
            // line 8
            echo '        <span class="trace-class">';
            echo $this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->abbrClass(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'class', [], 'any', false, false, false, 8));
            echo '</span>';
            if (!twig_test_empty(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'type', [], 'any', false, false, false, 8))) {
                echo '<span class="trace-type">';
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'type', [], 'any', false, false, false, 8), 'html', null, true);
                echo '</span>';
            }
            echo '<span class="trace-method">';
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'function', [], 'any', false, false, false, 8), 'html', null, true);
            echo '</span><span class="trace-arguments">(';
            echo $this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->formatArgs(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'args', [], 'any', false, false, false, 8));
            echo ')</span>
    ';
        }
        // line 10
        echo '
    ';
        // line 11
        if (((twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', true, true, false, 11)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 11), false)) : (false))) {
            // line 12
            echo '        ';
            $context['line_number'] = ((twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'line', [], 'any', true, true, false, 12)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'line', [], 'any', false, false, false, 12), 1)) : (1));
            // line 13
            echo '        ';
            $context['file_link'] = $this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->getFileLink(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 13), ($context['line_number'] ?? null));
            // line 14
            echo '        ';
            $context['file_path'] = twig_replace_filter(strip_tags($this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->formatFile(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 14), ($context['line_number'] ?? null))), [(' at line '.($context['line_number'] ?? null)) => '']);
            // line 15
            echo '        ';
            $context['file_path_parts'] = twig_split_filter($this->env, ($context['file_path'] ?? null), twig_constant('DIRECTORY_SEPARATOR'));
            // line 16
            echo '
        <span class="block trace-file-path">
            in
            <a href="';
            // line 19
            echo twig_escape_filter($this->env, ($context['file_link'] ?? null), 'html', null, true);
            echo '">';
            echo twig_escape_filter($this->env, twig_join_filter(twig_slice($this->env, ($context['file_path_parts'] ?? null), 0, -1), twig_constant('DIRECTORY_SEPARATOR')), 'html', null, true);
            echo twig_escape_filter($this->env, twig_constant('DIRECTORY_SEPARATOR'), 'html', null, true);
            echo '<strong>';
            echo twig_escape_filter($this->env, twig_last($this->env, ($context['file_path_parts'] ?? null)), 'html', null, true);
            echo '</strong></a>
            (line ';
            // line 20
            echo twig_escape_filter($this->env, ($context['line_number'] ?? null), 'html', null, true);
            echo ')
        </span>
    ';
        }
        // line 23
        echo '</div>
';
        // line 24
        if (((twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', true, true, false, 24)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 24), false)) : (false))) {
            // line 25
            echo '    <div id="trace-html-';
            echo twig_escape_filter($this->env, ((($context['prefix'] ?? null).'-').($context['i'] ?? null)), 'html', null, true);
            echo '" class="trace-code sf-toggle-content">
        ';
            // line 26
            echo twig_replace_filter($this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->fileExcerpt(twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'file', [], 'any', false, false, false, 26), twig_get_attribute($this->env, $this->source, ($context['trace'] ?? null), 'line', [], 'any', false, false, false, 26), 5), ['#DD0000' => '#183691', '#007700' => '#a71d5d', '#0000BB' => '#222222', '#FF8000' => '#969896']);
            // line 31
            echo '
    </div>
';
        }
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:trace.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [130 => 31,  128 => 26,  123 => 25,  121 => 24,  118 => 23,  112 => 20,  103 => 19,  98 => 16,  95 => 15,  92 => 14,  89 => 13,  86 => 12,  84 => 11,  81 => 10,  65 => 8,  63 => 7,  60 => 6,  55 => 4,  50 => 3,  48 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:trace.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/trace.html.twig');
    }
}
