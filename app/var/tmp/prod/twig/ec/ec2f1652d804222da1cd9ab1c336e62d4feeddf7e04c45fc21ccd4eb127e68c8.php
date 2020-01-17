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

/* TwigBundle:Exception:error.html.twig */
class __TwigTemplate_8298fe515bacb71413da9de42e676aa09b585cdad58b68c685c38ca9b34d96d2 extends \Twig\Template
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
        echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="';
        // line 4
        echo twig_escape_filter($this->env, $this->env->getCharset(), 'html', null, true);
        echo '" />
        <title>An Error Occurred: ';
        // line 5
        echo twig_escape_filter($this->env, ($context['status_text'] ?? null), 'html', null, true);
        echo '</title>
    </head>
    <body>
        <h1>Oops! An Error Occurred</h1>
        <h2>The server returned a "';
        // line 9
        echo twig_escape_filter($this->env, ($context['status_code'] ?? null), 'html', null, true);
        echo ' ';
        echo twig_escape_filter($this->env, ($context['status_text'] ?? null), 'html', null, true);
        echo '".</h2>

        <div>
            Something is broken. Please let us know what you were doing when this error occurred.
            We will fix it as soon as possible. Sorry for any inconvenience caused.
        </div>
    </body>
</html>
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:error.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [53 => 9,  46 => 5,  42 => 4,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:error.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/error.html.twig');
    }
}
