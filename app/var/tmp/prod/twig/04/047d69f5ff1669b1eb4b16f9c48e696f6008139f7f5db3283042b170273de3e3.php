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

/* LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig */
class __TwigTemplate_db8c0fd39d393614e62cda976ce4fa900367b20d7772861622f3e58075ee5478 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'toolbar'          => [$this, 'block_toolbar'],
            'menu'             => [$this, 'block_menu'],
            'panel'            => [$this, 'block_panel'],
            'table_pheanstalk' => [$this, 'block_table_pheanstalk'],
            'table_tube'       => [$this, 'block_table_tube'],
            'table_jobs'       => [$this, 'block_table_jobs'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return '@WebProfiler/Profiler/layout.html.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros       = $this->macros;
        $this->parent = $this->loadTemplate('@WebProfiler/Profiler/layout.html.twig', 'LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_toolbar($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo '    <div class="sf-toolbar-block sf-toolbar-block-leezy-pheanstalk sf-toolbar-status-normal">
        <a href="';
        // line 5
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath('_profiler', ['token' => ($context['token'] ?? null), 'panel' => ($context['name'] ?? null)]), 'html', null, true);
        echo '">
            <div class="sf-toolbar-icon">
                ';
        // line 7
        echo twig_include($this->env, $context, '@LeezyPheanstalk/Profiler/queue.svg');
        echo '
                <span class="sf-toolbar-value">';
        // line 8
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'jobCount', [], 'any', false, false, false, 8), 'html', null, true);
        echo '</span>
                <span class="sf-toolbar-label">Jobs</span>
            </div>
        </a>
        <div class="sf-toolbar-info">
            <div class="sf-toolbar-status">
                <b>Pheanstalks</b>
                <span>';
        // line 15
        echo twig_escape_filter($this->env, twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'pheanstalks', [], 'any', false, false, false, 15)), 'html', null, true);
        echo '</span>
            </div>
            <div class="sf-toolbar-status">
                <b>Tubes</b>
                <span>';
        // line 19
        echo twig_escape_filter($this->env, twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'tubes', [], 'any', false, false, false, 19)), 'html', null, true);
        echo '</span>
            </div>
            <div class="sf-toolbar-status">
                <b>Jobs</b>
                <span>';
        // line 23
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'jobCount', [], 'any', false, false, false, 23), 'html', null, true);
        echo '</span>
            </div>
        </div>
    </div>
';
    }

    // line 29
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 30
        echo '    <span class="label">
        <span class="icon">';
        // line 31
        echo twig_include($this->env, $context, '@LeezyPheanstalk/Profiler/queue.svg');
        echo '</span>
        <strong>Pheanstalk</strong>
        <span class="count">
            <span>';
        // line 34
        echo twig_escape_filter($this->env, twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'pheanstalks', [], 'any', false, false, false, 34)), 'html', null, true);
        echo '</span>
            <span>';
        // line 35
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'jobCount', [], 'any', false, false, false, 35), 'html', null, true);
        echo ' Jb</span>
        </span>
    </span>
';
    }

    // line 40
    public function block_panel($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 41
        echo '    <h2>Pheanstalks</h2>
    ';
        // line 42
        $context['data'] = twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'pheanstalks', [], 'any', false, false, false, 42);
        // line 43
        echo '    ';
        $this->displayBlock('table_pheanstalk', $context, $blocks);
        echo '

    <h2>Tubes</h2>
    ';
        // line 46
        $context['data'] = twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'tubes', [], 'any', false, false, false, 46);
        // line 47
        echo '    ';
        $this->displayBlock('table_tube', $context, $blocks);
        echo '

    <h2>Jobs</h2>
    ';
        // line 50
        $context['data'] = twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'jobs', [], 'any', false, false, false, 50);
        // line 51
        echo '    ';
        $this->displayBlock('table_jobs', $context, $blocks);
        echo '
';
    }

    // line 54
    public function block_table_pheanstalk($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 55
        echo '    <table>
        ';
        // line 56
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['data'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['row']) {
            // line 57
            echo '            <tr>
                <th colspan="2" style="text-align: center;vertical-align: middle;padding: 5px 0">
                    ';
            // line 59
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'name', [], 'any', false, false, false, 59), 'html', null, true);
            echo ' ';
            if (twig_get_attribute($this->env, $this->source, $context['row'], 'default', [], 'any', false, false, false, 59)) {
                echo '(default)';
            }
            // line 60
            echo '                </th>
            </tr>
            <tbody>
                <tr>
                    <th>host</th>
                    <td>';
            // line 65
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'host', [], 'any', false, false, false, 65), 'html', null, true);
            echo '</td>
                </tr>
                <tr>
                    <th>port</th>
                    <td>';
            // line 69
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'port', [], 'any', false, false, false, 69), 'html', null, true);
            echo '</td>
                </tr>
                <tr>
                    <th>timeout</th>
                    <td>';
            // line 73
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'timeout', [], 'any', false, false, false, 73), 'html', null, true);
            echo '</td>
                </tr>
                <tr>
                    <th>listening</th>
                    <td>';
            // line 77
            echo (twig_get_attribute($this->env, $this->source, $context['row'], 'listening', [], 'any', false, false, false, 77)) ? ('yes') : ('no');
            echo '</td>
                </tr>
                <tr>
                    <th>default</th>
                    <td>';
            // line 81
            echo (twig_get_attribute($this->env, $this->source, $context['row'], 'default', [], 'any', false, false, false, 81)) ? ('yes') : ('no');
            echo '</td>
                </tr>
                ';
            // line 83
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context['row'], 'stats', [], 'any', false, false, false, 83));
            foreach ($context['_seq'] as $context['stat'] => $context['data']) {
                // line 84
                echo '                <tr>
                    <th style="width: 150px">';
                // line 85
                echo twig_escape_filter($this->env, $context['stat'], 'html', null, true);
                echo '</th>
                    <td>';
                // line 86
                echo twig_escape_filter($this->env, $context['data'], 'html', null, true);
                echo '</td>
                </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['stat'], $context['data'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 89
            echo '            </tbody>
        ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 91
        echo '    </table>
';
    }

    // line 94
    public function block_table_tube($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 95
        echo '    <table>
        ';
        // line 96
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['data'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['row']) {
            // line 97
            echo '            <tr>
                <th colspan="2" style="text-align: center;vertical-align: middle;padding: 5px 0">
                    ';
            // line 99
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'name', [], 'any', false, false, false, 99), 'html', null, true);
            echo " tube on '";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'pheanstalk', [], 'any', false, false, false, 99), 'html', null, true);
            echo "' pheanstalk
                </th>
            </tr>
            <tbody>
                <tr>
                    <th style=\"width: 150px\">pheanstalk</th>
                    <td>";
            // line 105
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['row'], 'pheanstalk', [], 'any', false, false, false, 105), 'html', null, true);
            echo '</td>
                </tr>
                ';
            // line 107
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context['row'], 'stats', [], 'any', false, false, false, 107));
            foreach ($context['_seq'] as $context['stat'] => $context['data']) {
                // line 108
                echo '                    <tr>
                        <th style="width: 150px">';
                // line 109
                echo twig_escape_filter($this->env, $context['stat'], 'html', null, true);
                echo '</th>
                        <td>';
                // line 110
                echo twig_escape_filter($this->env, $context['data'], 'html', null, true);
                echo '</td>
                    </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['stat'], $context['data'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 113
            echo '            </tbody>
        ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 115
        echo '    </table>
';
    }

    // line 118
    public function block_table_jobs($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 119
        echo '    ';
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['data'] ?? null));
        foreach ($context['_seq'] as $context['tube'] => $context['types']) {
            // line 120
            echo '        <table>
            <th colspan="2" style="text-align: center;vertical-align: middle;padding: 5px 0">
                ';
            // line 122
            echo twig_escape_filter($this->env, $context['tube'], 'html', null, true);
            echo '
            </th>
            ';
            // line 124
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable($context['types']);
            foreach ($context['_seq'] as $context['job_type'] => $context['job']) {
                // line 125
                echo '                <tr>
                    <th rowspan="2">Next ';
                // line 126
                echo twig_escape_filter($this->env, $context['job_type'], 'html', null, true);
                echo '</th>
                    <td>Job ID: ';
                // line 127
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['job'], 'id', [], 'any', false, false, false, 127), 'html', null, true);
                echo '</td>
                </tr>
                <tr>
                    <td>
                        <div style="float:left;width:100%;word-spacing:normal;word-wrap:break-word;max-width:720px;">
                            Data: ';
                // line 132
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['job'], 'data', [], 'any', false, false, false, 132), 'html', null, true);
                echo '
                        </div>
                    </td>
                </tr>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['job_type'], $context['job'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 137
            echo '        </table>
    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['tube'], $context['types'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return 'LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [363 => 137,  352 => 132,  344 => 127,  340 => 126,  337 => 125,  333 => 124,  328 => 122,  324 => 120,  319 => 119,  315 => 118,  310 => 115,  303 => 113,  294 => 110,  290 => 109,  287 => 108,  283 => 107,  278 => 105,  267 => 99,  263 => 97,  259 => 96,  256 => 95,  252 => 94,  247 => 91,  240 => 89,  231 => 86,  227 => 85,  224 => 84,  220 => 83,  215 => 81,  208 => 77,  201 => 73,  194 => 69,  187 => 65,  180 => 60,  174 => 59,  170 => 57,  166 => 56,  163 => 55,  159 => 54,  152 => 51,  150 => 50,  143 => 47,  141 => 46,  134 => 43,  132 => 42,  129 => 41,  125 => 40,  117 => 35,  113 => 34,  107 => 31,  104 => 30,  100 => 29,  91 => 23,  84 => 19,  77 => 15,  67 => 8,  63 => 7,  58 => 5,  55 => 4,  51 => 3,  40 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig', '/Users/mike.shaw/sites/mautic/vendor/leezy/pheanstalk-bundle/src/Resources/views/Profiler/pheanstalk.html.twig');
    }
}
