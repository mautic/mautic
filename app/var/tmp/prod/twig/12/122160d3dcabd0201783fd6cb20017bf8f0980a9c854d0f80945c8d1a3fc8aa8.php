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

/* DoctrineBundle:Collector:explain.html.twig */
class __TwigTemplate_9e4a35a7988dc4fc3626ced102f39e2e92daa0bf8d42ca004ba38932c2951ff2 extends \Twig\Template
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
        if ((twig_length_filter($this->env, (($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context['data'] ?? null)) && is_array($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4) || $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 instanceof ArrayAccess ? ($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4[0] ?? null) : null)) > 1)) {
            // line 2
            echo '    ';
            // line 3
            echo '    <table style="margin: 5px 0;">
        <thead>
            <tr>
                ';
            // line 6
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(twig_get_array_keys_filter((($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = ($context['data'] ?? null)) && is_array($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144) || $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 instanceof ArrayAccess ? ($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144[0] ?? null) : null)));
            foreach ($context['_seq'] as $context['_key'] => $context['label']) {
                // line 7
                echo '                    <th>';
                echo twig_escape_filter($this->env, $context['label'], 'html', null, true);
                echo '</th>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['label'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 9
            echo '            </tr>
        </thead>
        <tbody>
            ';
            // line 12
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['data'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['row']) {
                // line 13
                echo '            <tr>
                ';
                // line 14
                $context['_parent'] = $context;
                $context['_seq']    = twig_ensure_traversable($context['row']);
                foreach ($context['_seq'] as $context['key'] => $context['item']) {
                    // line 15
                    echo '                    <td>';
                    echo twig_escape_filter($this->env, twig_replace_filter($context['item'], [',' => ', ']), 'html', null, true);
                    echo '</td>
                ';
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['key'], $context['item'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 17
                echo '            </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 19
            echo '        </tbody>
    </table>
';
        } else {
            // line 22
            echo '    ';
            // line 23
            echo '    <pre style="margin: 5px 0;">';
            // line 24
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['data'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['row']) {
                // line 25
                echo twig_escape_filter($this->env, twig_first($this->env, $context['row']), 'html', null, true);
                echo '
';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 27
            echo '</pre>
';
        }
    }

    public function getTemplateName()
    {
        return 'DoctrineBundle:Collector:explain.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [112 => 27,  104 => 25,  100 => 24,  98 => 23,  96 => 22,  91 => 19,  84 => 17,  75 => 15,  71 => 14,  68 => 13,  64 => 12,  59 => 9,  50 => 7,  46 => 6,  41 => 3,  39 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'DoctrineBundle:Collector:explain.html.twig', '/Users/mike.shaw/sites/mautic/vendor/doctrine/doctrine-bundle/Resources/views/Collector/explain.html.twig');
    }
}
