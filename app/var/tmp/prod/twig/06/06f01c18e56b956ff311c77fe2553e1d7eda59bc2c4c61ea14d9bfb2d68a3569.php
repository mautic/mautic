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

/* TwigBundle:Exception:traces_text.html.twig */
class __TwigTemplate_65782ed66f78fe5966db22a94ee18a30aad0435efaa2633dc75dc1b475de5d40 extends \Twig\Template
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
        echo '<table class="trace trace-as-text">
    <thead class="trace-head">
        <tr>
            <th class="sf-toggle" data-toggle-selector="#trace-text-';
        // line 4
        echo twig_escape_filter($this->env, ($context['index'] ?? null), 'html', null, true);
        echo '" data-toggle-initial="';
        echo ((1 == ($context['index'] ?? null))) ? ('display') : ('');
        echo '">
                <h3 class="trace-class">
                    ';
        // line 6
        if ((($context['num_exceptions'] ?? null) > 1)) {
            // line 7
            echo '                        <span class="text-muted">[';
            echo twig_escape_filter($this->env, ((($context['num_exceptions'] ?? null) - ($context['index'] ?? null)) + 1), 'html', null, true);
            echo '/';
            echo twig_escape_filter($this->env, ($context['num_exceptions'] ?? null), 'html', null, true);
            echo ']</span>
                    ';
        }
        // line 9
        echo '                    ';
        echo twig_escape_filter($this->env, twig_last($this->env, twig_split_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'class', [], 'any', false, false, false, 9), '\\')), 'html', null, true);
        echo '
                    <span class="icon icon-close">';
        // line 10
        echo twig_include($this->env, $context, '@Twig/images/icon-minus-square-o.svg');
        echo '</span>
                    <span class="icon icon-open">';
        // line 11
        echo twig_include($this->env, $context, '@Twig/images/icon-plus-square-o.svg');
        echo '</span>
                </h3>
            </th>
        </tr>
    </thead>

    <tbody id="trace-text-';
        // line 17
        echo twig_escape_filter($this->env, ($context['index'] ?? null), 'html', null, true);
        echo '">
        <tr>
            <td>
                ';
        // line 20
        if (twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'trace', [], 'any', false, false, false, 20))) {
            // line 21
            echo '                <pre class="stacktrace">';
            // line 22
            ob_start(function () { return ''; });
            // line 23
            echo twig_include($this->env, $context, '@Twig/Exception/traces.txt.twig', ['exception' => ($context['exception'] ?? null), 'format' => 'html'], false);
            echo '
                ';
            $___internal_39e4d87e86c7900d95f15722adcf07b5c221d63657e7be9ab7144c54ee9b49a1_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 22
            echo twig_escape_filter($this->env, $___internal_39e4d87e86c7900d95f15722adcf07b5c221d63657e7be9ab7144c54ee9b49a1_, 'html');
            // line 25
            echo '                </pre>
                ';
        }
        // line 27
        echo '            </td>
        </tr>
    </tbody>
</table>
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:traces_text.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [100 => 27,  96 => 25,  94 => 22,  89 => 23,  87 => 22,  85 => 21,  83 => 20,  77 => 17,  68 => 11,  64 => 10,  59 => 9,  51 => 7,  49 => 6,  42 => 4,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:traces_text.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/traces_text.html.twig');
    }
}
