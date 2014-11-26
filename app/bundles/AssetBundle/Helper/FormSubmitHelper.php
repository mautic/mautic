<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormSubmitHelper
 *
 * @package Mautic\AssetBundle\Helper
 */
class FormSubmitHelper
{

    /**
     * @param       $action
     *
     * @return array
     */
    public static function onFormSubmit(Action $action, MauticFactory $factory)
    {
        $properties = $action->getProperties();

        $assetId  = $properties['asset'];

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model  = $factory->getModel('asset');
        $asset  = $model->getEntity($assetId);

        //make sure the asset still exists and is published
        if ($asset != null && $asset->isPublished()) {
            //register a callback after the other actions have been fired
            return array(
                'callback' => '\Mautic\AssetBundle\Helper\FormSubmitHelper::downloadFile',
                'asset'    => $asset,
                'message'  => $properties['message']
            );
        }
    }

    public static function downloadFile(Form $form, Asset $asset, MauticFactory $factory, $message)
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $factory->getModel('asset');
        $url   = $model->generateUrl($asset, true, array('form', $form->getId()));
        $msg   = $message . $factory->getTranslator()->trans('mautic.asset.asset.submitaction.downloadfile.msg', array(
            '%url%' => $url
        ));

        //@todo - give option to choose a template
        $content = $factory->getTemplating()->renderResponse('MauticEmailBundle::message.html.php', array(
            'message'  => $msg,
            'type'     => 'notice',
            'template' => $factory->getParameter('theme')
        ))->getContent();

        return new Response($content);
    }
}
