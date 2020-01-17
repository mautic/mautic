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

/* OldSoundRabbitMqBundle:Collector:collector.html.twig */
class __TwigTemplate_51e851f5d2b4b01864bc4727b3f5a14187daab6b9609c0c11e710d493a1f0e4f extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'toolbar' => [$this, 'block_toolbar'],
            'menu'    => [$this, 'block_menu'],
            'panel'   => [$this, 'block_panel'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return 'WebProfilerBundle:Profiler:layout.html.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros       = $this->macros;
        $this->parent = $this->loadTemplate('WebProfilerBundle:Profiler:layout.html.twig', 'OldSoundRabbitMqBundle:Collector:collector.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_toolbar($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo '    ';
        if (twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesCount', [], 'any', false, false, false, 4)) {
            // line 5
            echo '        ';
            ob_start(function () { return ''; });
            // line 6
            echo '            <img width="28" height="28" alt="RabbitMQ" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqlJREFUeNrsV01oE0EU/maz2U1tUFqtAY1QsaQVJKmHFjEF0R5E9CQIHhQF0UNBBL2IJ3tUevGiIOjFg0IpiErRXppD0VOpJCohQqWNYmzVtknaZtP9cWaTtKadDU3cmB76wUvCzJt5386+970JMQwDDMZQ3wmkf/bAIQKqAuz23yKHzkfAgRF/78Zo/9PlAZ3u4T95hbQEv6NMiOZnrL+bPO8dhKQBhACaDmzZ6kWL/xh2BGZWLyJDt+9g9M0pyEJuQKH+8bcDuBY6XC6B3A5fPnRiNg0sLALzC/Q7A/yYasfnsYPcVePhdtOH+Rb8J2IBVIDcCTicikmFFB4xT00QVe4qh5Q1ff72FyWlEgICaoxNApsENjgBqzIkgs4Z0+wjwOqahVica+DOZ1LuZQ0o+CvJxsqFiEeAUXt99ybi4QCVZx2GIdA+oSIRbcPvr3uKVrLf6V8C+o6OwBt4B21JtozI+oarPo0jl3ux06eIlo5OaonxLkzc7ypWQWp1HMJsPBIKYoxaKbDel6WmZWWcvXdDLOnozNt64VqnH20dmP3WXNsqEBxL1jnwLzA4r6fsJKwUyqraUvMRJA4x2wmwYL7OFwhefARRzpi6kEzswvCDq5ie7LDKJdG24PWNUVx6cgYeX7ZorsE7iYcXQtB1bsbZk4RMtOq2za0JzuBpjUKSLV+BfVVg6Py9dDZO/kMzYmrJLzc1d0TVJMBUMDXtQWTwwJq58MvTyGQsI4m2EVDmm/H43AD2H38FyUVzgXbM1FQTPtLru2itB/aVIdspNePDyLPrRSIklT5ne4WI16g27o3IcKycgEpbo4ZSyWovWCxNda4QaNr3CRLJXRZItR88f+57O4bNNCn8O0Ys1EavYNsLbbJ6BDQnZHcSrd2RYgI1wh8BBgAR2M5KdN1kRwAAAABJRU5ErkJggg==" />
            <span class="sf-toolbar-status">';
            // line 7
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesCount', [], 'any', false, false, false, 7), 'html', null, true);
            echo '</span>
        ';
            $context['icon'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 9
            echo '        ';
            ob_start(function () { return ''; });
            // line 10
            echo '            <div class="sf-toolbar-info-piece">
                <b>Messages</b>
                <span>';
            // line 12
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesCount', [], 'any', false, false, false, 12), 'html', null, true);
            echo '</span>
            </div>
        ';
            $context['text'] = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 15
            echo '        ';
            $this->loadTemplate('WebProfilerBundle:Profiler:toolbar_item.html.twig', 'OldSoundRabbitMqBundle:Collector:collector.html.twig', 15)->display(twig_array_merge($context, ['link' => ($context['profiler_url'] ?? null)]));
            // line 16
            echo '    ';
        }
    }

    // line 19
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 20
        echo '<span class="label">
    <span class="icon"><img alt="RabbitMQ" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqlJREFUeNrsV01oE0EU/maz2U1tUFqtAY1QsaQVJKmHFjEF0R5E9CQIHhQF0UNBBL2IJ3tUevGiIOjFg0IpiErRXppD0VOpJCohQqWNYmzVtknaZtP9cWaTtKadDU3cmB76wUvCzJt5386+970JMQwDDMZQ3wmkf/bAIQKqAuz23yKHzkfAgRF/78Zo/9PlAZ3u4T95hbQEv6NMiOZnrL+bPO8dhKQBhACaDmzZ6kWL/xh2BGZWLyJDt+9g9M0pyEJuQKH+8bcDuBY6XC6B3A5fPnRiNg0sLALzC/Q7A/yYasfnsYPcVePhdtOH+Rb8J2IBVIDcCTicikmFFB4xT00QVe4qh5Q1ff72FyWlEgICaoxNApsENjgBqzIkgs4Z0+wjwOqahVica+DOZ1LuZQ0o+CvJxsqFiEeAUXt99ybi4QCVZx2GIdA+oSIRbcPvr3uKVrLf6V8C+o6OwBt4B21JtozI+oarPo0jl3ux06eIlo5OaonxLkzc7ypWQWp1HMJsPBIKYoxaKbDel6WmZWWcvXdDLOnozNt64VqnH20dmP3WXNsqEBxL1jnwLzA4r6fsJKwUyqraUvMRJA4x2wmwYL7OFwhefARRzpi6kEzswvCDq5ie7LDKJdG24PWNUVx6cgYeX7ZorsE7iYcXQtB1bsbZk4RMtOq2za0JzuBpjUKSLV+BfVVg6Py9dDZO/kMzYmrJLzc1d0TVJMBUMDXtQWTwwJq58MvTyGQsI4m2EVDmm/H43AD2H38FyUVzgXbM1FQTPtLru2itB/aVIdspNePDyLPrRSIklT5ne4WI16g27o3IcKycgEpbo4ZSyWovWCxNda4QaNr3CRLJXRZItR88f+57O4bNNCn8O0Ys1EavYNsLbbJ6BDQnZHcSrd2RYgI1wh8BBgAR2M5KdN1kRwAAAABJRU5ErkJggg==" /></span>
    <strong>RabbitMQ</strong>
    <span class="count">
        <span>';
        // line 24
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesCount', [], 'any', false, false, false, 24), 'html', null, true);
        echo '</span>
    </span>
</span>
';
    }

    // line 29
    public function block_panel($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 30
        echo '    <h2>Messages</h2>
    ';
        // line 31
        if (twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesCount', [], 'any', false, false, false, 31)) {
            // line 32
            echo '        <table>
            <thead>
                <tr>
                    <th scope="col">Exchange</th>
                    <th scope="col">Message body</th>
                </tr>
            </thead>
            <tbody>
                ';
            // line 40
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context['collector'] ?? null), 'publishedMessagesLog', [], 'any', false, false, false, 40));
            foreach ($context['_seq'] as $context['_key'] => $context['log']) {
                // line 41
                echo '                <tr>
                    <td>';
                // line 42
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['log'], 'exchange', [], 'any', false, false, false, 42), 'html', null, true);
                echo '</td>
                    <td>';
                // line 43
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context['log'], 'msg', [], 'any', false, false, false, 43), 'body', [], 'any', false, false, false, 43), 'html', null, true);
                echo '</td>
                </tr>
                ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['log'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 46
            echo '            </tbody>
        </table>
    ';
        } else {
            // line 49
            echo '        <p>
            <em>No messages were sent.</em>
        </p>
    ';
        }
    }

    public function getTemplateName()
    {
        return 'OldSoundRabbitMqBundle:Collector:collector.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [149 => 49,  144 => 46,  135 => 43,  131 => 42,  128 => 41,  124 => 40,  114 => 32,  112 => 31,  109 => 30,  105 => 29,  97 => 24,  91 => 20,  87 => 19,  82 => 16,  79 => 15,  73 => 12,  69 => 10,  66 => 9,  61 => 7,  58 => 6,  55 => 5,  52 => 4,  48 => 3,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'OldSoundRabbitMqBundle:Collector:collector.html.twig', '/Users/mike.shaw/sites/mautic/vendor/php-amqplib/rabbitmq-bundle/Resources/views/Collector/collector.html.twig');
    }
}
