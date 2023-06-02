<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

trait BuilderControllerTrait
{
    /**
     * Get assets for builder.
     */
    protected function getAssetsForBuilder(AssetsHelper $assetsHelper, Translator $translatorHelper, Request $request, RouterInterface $routerHelper, CoreParametersHelper $coreParametersHelper): string
    {
        // /** @var RouterInterface $routerHelper */
        // $routerHelper = $this->get('router');
        $assetsHelper
            ->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->addScriptDeclaration("var mauticBasePath      = '".$request->getBasePath()."';")
            ->addScriptDeclaration("var mauticAjaxUrl       = '".$routerHelper->generate('mautic_core_ajax')."';")
            ->addScriptDeclaration("var mauticBaseUrl       = '".$routerHelper->generate('mautic_base_index')."';")
            ->addScriptDeclaration("var mauticAssetPrefix   = '".$assetsHelper->getAssetPrefix(true)."';")
            ->addScriptDeclaration('var mauticLang          = '.$translatorHelper->getJsLang().';')
            ->addScriptDeclaration('var mauticFroalaEnabled = '.(int) $coreParametersHelper->get('load_froala_assets').';')
            ->addCustomDeclaration($assetsHelper->getSystemScripts(true, true))
            ->addStylesheet('app/bundles/CoreBundle/Assets/css/libraries/builder.css');

        $builderAssets = $assetsHelper->getHeadDeclarations();

        // reset context to main
        $assetsHelper->setContext();

        return $builderAssets;
    }

    /**
     * @return array
     */
    protected function buildSlotForms($slotTypes)
    {
        foreach ($slotTypes as $key => $slotType) {
            if (!empty($slotType['form'])) {
                $slotForm                = $this->formFactory->create($slotType['form']);
                $slotTypes[$key]['form'] = $slotForm->createView();
            }
        }

        return $slotTypes;
    }
}
