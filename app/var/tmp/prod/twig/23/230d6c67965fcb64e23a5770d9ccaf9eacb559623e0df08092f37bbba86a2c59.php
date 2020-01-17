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

/* TwigBundle:Exception:logs.html.twig */
class __TwigTemplate_b19c65771da1e5564657b9d43d20df0dc4237db8efd77458f183e818ee03e187 extends \Twig\Template
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
        $context['channel_is_defined'] = twig_get_attribute($this->env, $this->source, twig_first($this->env, ($context['logs'] ?? null)), 'channel', [], 'any', true, true, false, 1);
        // line 2
        echo '
<table class="logs">
    <thead>
        <tr>
            <th>Level</th>
            ';
        // line 7
        if (($context['channel_is_defined'] ?? null)) {
            echo '<th>Channel</th>';
        }
        // line 8
        echo '            <th class="full-width">Message</th>
        </tr>
    </thead>

    <tbody>
    ';
        // line 13
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['logs'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['log']) {
            // line 14
            echo '        ';
            if ((twig_get_attribute($this->env, $this->source, $context['log'], 'priority', [], 'any', false, false, false, 14) >= 400)) {
                // line 15
                echo '            ';
                $context['status'] = 'error';
                // line 16
                echo '        ';
            } elseif ((twig_get_attribute($this->env, $this->source, $context['log'], 'priority', [], 'any', false, false, false, 16) >= 300)) {
                // line 17
                echo '            ';
                $context['status'] = 'warning';
                // line 18
                echo '        ';
            } else {
                // line 19
                echo '            ';
                $context['severity'] = ((twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context['log'], 'context', [], 'any', false, true, false, 19), 'exception', [], 'any', false, true, false, 19), 'severity', [], 'any', true, true, false, 19)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context['log'], 'context', [], 'any', false, true, false, 19), 'exception', [], 'any', false, true, false, 19), 'severity', [], 'any', false, false, false, 19), false)) : (false));
                // line 20
                echo '            ';
                $context['status'] = ((((($context['severity'] ?? null) === constant('E_DEPRECATED')) || (($context['severity'] ?? null) === constant('E_USER_DEPRECATED')))) ? ('warning') : ('normal'));
                // line 21
                echo '        ';
            }
            // line 22
            echo '        <tr class="status-';
            echo twig_escape_filter($this->env, ($context['status'] ?? null), 'html', null, true);
            echo '">
            <td class="text-small" nowrap>
                <span class="colored text-bold">';
            // line 24
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['log'], 'priorityName', [], 'any', false, false, false, 24), 'html', null, true);
            echo '</span>
                <span class="text-muted newline">';
            // line 25
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, twig_get_attribute($this->env, $this->source, $context['log'], 'timestamp', [], 'any', false, false, false, 25), 'H:i:s'), 'html', null, true);
            echo '</span>
            </td>
            ';
            // line 27
            if (($context['channel_is_defined'] ?? null)) {
                // line 28
                echo '                <td class="text-small text-bold nowrap">
                    ';
                // line 29
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['log'], 'channel', [], 'any', false, false, false, 29), 'html', null, true);
                echo '
                </td>
            ';
            }
            // line 32
            echo '            <td>';
            echo $this->extensions['Symfony\Bridge\Twig\Extension\CodeExtension']->formatLogMessage(twig_get_attribute($this->env, $this->source, $context['log'], 'message', [], 'any', false, false, false, 32), twig_get_attribute($this->env, $this->source, $context['log'], 'context', [], 'any', false, false, false, 32));
            echo '</td>
        </tr>
    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['log'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 35
        echo '    </tbody>
</table>
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:logs.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [121 => 35,  111 => 32,  105 => 29,  102 => 28,  100 => 27,  95 => 25,  91 => 24,  85 => 22,  82 => 21,  79 => 20,  76 => 19,  73 => 18,  70 => 17,  67 => 16,  64 => 15,  61 => 14,  57 => 13,  50 => 8,  46 => 7,  39 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:logs.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/logs.html.twig');
    }
}
