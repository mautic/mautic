<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Helper;

use Doctrine\ORM\NoResultException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormSubmitHelper.
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
        $assetId    = $properties['asset'];
        $categoryId = isset($properties['category']) ? $properties['category'] : null;
        $model      = $factory->getModel('asset');
        $asset      = null;

        if (null !== $assetId) {
            $asset = $model->getEntity($assetId);
        } elseif (null !== $categoryId) {
            try {
                $asset = $model->getRepository()->getLatestAssetForCategory($categoryId);
            } catch (NoResultException $e) {
                $asset = null;
            }
        }

        //make sure the asset still exists and is published
        if ($asset !== null && $asset->isPublished()) {
            //register a callback after the other actions have been fired
            return [
                'callback' => '\Mautic\AssetBundle\Helper\FormSubmitHelper::downloadFile',
                'form'     => $action->getForm(),
                'asset'    => $asset,
                'message'  => (isset($properties['message'])) ? $properties['message'] : '',
            ];
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
        $url   = $model->generateUrl($asset, true, ['form', $form->getId()]);

        if ($messengerMode) {
            return ['download' => $url];
        }

        $msg = $message.$factory->getTranslator()->trans('mautic.asset.asset.submitaction.downloadfile.msg', [
            '%url%' => $url,
        ]);

        $analytics = $factory->getHelper('template.analytics')->getCode();

        if (!empty($analytics)) {
            $factory->getHelper('template.assets')->addCustomDeclaration($analytics);
        }

        $logicalName = $factory->getHelper('theme')->checkForTwigTemplate(':'.$factory->getParameter('theme').':message.html.php');

        $content = $factory->getTemplating()->renderResponse($logicalName, [
            'message'  => $msg,
            'type'     => 'notice',
            'template' => $factory->getParameter('theme'),
        ])->getContent();

        return new Response($content);
    }
}
