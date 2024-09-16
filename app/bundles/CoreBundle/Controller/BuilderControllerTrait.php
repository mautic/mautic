<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Routing\RouterInterface;

trait BuilderControllerTrait
{
    /**
     * Get assets for builder.
     */
    protected function getAssetsForBuilder()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->get('templating.helper.assets');
        /** @var RouterInterface $routerHelper */
        $routerHelper = $this->get('router');
        $translator   = $this->get('templating.helper.translator');
        $assetsHelper
            ->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->addScriptDeclaration("var mauticBasePath    = '".$this->request->getBasePath()."';")
            ->addScriptDeclaration("var mauticAjaxUrl     = '".$routerHelper->generate('mautic_core_ajax')."';")
            ->addScriptDeclaration("var mauticBaseUrl     = '".$routerHelper->generate('mautic_base_index')."';")
            ->addScriptDeclaration("var mauticAssetPrefix = '".$assetsHelper->getAssetPrefix(true)."';")
            ->addScriptDeclaration('var mauticLang        = '.$translator->getJsLang().';')
            ->addCustomDeclaration($assetsHelper->getSystemScripts(true, true))
            ->addStylesheet('app/bundles/CoreBundle/Assets/css/libraries/builder.css');

        $builderAssets = $assetsHelper->getHeadDeclarations();

        // reset context to main
        $assetsHelper->setContext();

        return $builderAssets;
    }

    /**
     * @param $slotTypes
     *
     * @return array
     */
    protected function buildSlotForms($slotTypes)
    {
        foreach ($slotTypes as $key => $slotType) {
            if (!empty($slotType['form'])) {
                $slotForm                = $this->get('form.factory')->create($slotType['form']);
                $slotTypes[$key]['form'] = $slotForm->createView();
            }
        }

        return $slotTypes;
    }
}
