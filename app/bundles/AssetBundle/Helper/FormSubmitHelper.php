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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormSubmitHelper
 *
 * @package Mautic\AssetBundle\Helper
 */
class FormSubmitHelper
{

    /**
     * @param Action        $action
     * @param MauticFactory $factory
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
        $form   = $action->getForm();

        //make sure the asset still exists and is published
        if ($asset != null && $asset->isPublished()) {
            //register a callback after the other actions have been fired
            return array(
                'callback' => '\Mautic\AssetBundle\Helper\FormSubmitHelper::downloadFile',
                'form'     => $form,
                'asset'    => $asset,
                'message'  => (isset($properties['message'])) ? $properties['message'] : ''
            );
        }
    }

    /**
     * @param Form          $form
     * @param Asset         $asset
     * @param MauticFactory $factory
     * @param               $message
     * @param               $messageMode
     *
     * @return RedirectResponse|Response
     */
    public static function downloadFile(Form $form, Asset $asset, MauticFactory $factory, $message, $messengerMode)
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $factory->getModel('asset');
        $url   = $model->generateUrl($asset, true, array('form', $form->getId()));

        if ($messengerMode) {
            return array('download' => $url);
        }

        $msg = $message . $factory->getTranslator()->trans('mautic.asset.asset.submitaction.downloadfile.msg', array(
            '%url%' => $url
        ));

        $content = $factory->getTemplating()->renderResponse('MauticCoreBundle::message.html.php', array(
            'message'  => $msg,
            'type'     => 'notice',
            'template' => $factory->getParameter('theme')
        ))->getContent();

        return new Response($content);
    }
}
