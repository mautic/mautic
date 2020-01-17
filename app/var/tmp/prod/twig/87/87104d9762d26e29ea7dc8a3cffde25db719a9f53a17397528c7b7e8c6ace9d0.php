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

/* TwigBundle:Exception:error.json.twig */
class __TwigTemplate_bea1f82ff023b52730361056dac7c05f1aaeb8f610d3a87069b1e1adf09bf3d1 extends \Twig\Template
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
        echo json_encode(['error' => ['code' => ($context['status_code'] ?? null), 'message' => ($context['status_text'] ?? null)]]);
        echo '
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:error.json.twig';
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
        return new Source('', 'TwigBundle:Exception:error.json.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/error.json.twig');
    }
}
