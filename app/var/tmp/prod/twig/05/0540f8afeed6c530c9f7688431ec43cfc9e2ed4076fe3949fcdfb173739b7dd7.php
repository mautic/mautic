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

/* TwigBundle:Exception:exception.json.twig */
class __TwigTemplate_e3435d532312cadeae47b65f3896e126417a1cda712a43252c2a679ad2070d8f extends \Twig\Template
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
        echo json_encode(['error' => ['code' => ($context['status_code'] ?? null), 'message' => ($context['status_text'] ?? null), 'exception' => twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'toarray', [], 'any', false, false, false, 1)]]);
        echo '
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:exception.json.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:exception.json.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/exception.json.twig');
    }
}
