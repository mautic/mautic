<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;

trait BuilderControllerTrait
{
    /**
     * Get assets for builder.
     */
    protected function getAssetsForBuilder()
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->get('templating.helper.assets');
        /** @var \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper $routerHelper */
        $routerHelper = $this->get('templating.helper.router');

        $assetsHelper
            ->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->addScriptDeclaration("var mauticBasePath    = '".$this->request->getBasePath()."';")
            ->addScriptDeclaration("var mauticAjaxUrl     = '".$routerHelper->generate('mautic_core_ajax')."';")
            ->addScriptDeclaration("var mauticBaseUrl     = '".$routerHelper->generate('mautic_base_index')."';")
            ->addScriptDeclaration("var mauticAssetPrefix = '".$assetsHelper->getAssetPrefix(true)."';")
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
            if (isset($slotType['form'])) {
                $slotForm                = $this->get('form.factory')->create($slotType['form']);
                $slotTypes[$key]['form'] = $slotForm->createView();
            }
        }

        return $slotTypes;
    }
}
