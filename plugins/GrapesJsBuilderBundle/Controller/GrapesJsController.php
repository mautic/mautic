<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GrapesJsController extends CommonController
{
    public const OBJECT_TYPE = ['email', 'page'];

    /**
     * Activate the custom builder.
     *
     * @param string $objectType
     * @param int    $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function builderAction(
            Request $request,
            LoggerInterface $mauticLogger,
            ThemeHelper $themeHelper,
            SlotsHelper $slotsHelper,
            AssetsHelper $assetsHelper,
            FormFactoryInterface $formFactory,
            $objectType,
            $objectId
    ) {
        if (!in_array($objectType, self::OBJECT_TYPE)) {
            throw new \Exception('Object not authorized to load custom builder', Response::HTTP_CONFLICT);
        }

        /** @var \Mautic\EmailBundle\Model\EmailModel|\Mautic\PageBundle\Model\PageModel $model */
        $model      = $this->getModel($objectType);
        $aclToCheck = 'email:emails:';

        if ('page' === $objectType) {
            $aclToCheck = 'page:pages:';
        }

        // permission check
        if (str_contains((string) $objectId, 'new')) {
            $isNew = true;

            if (!$this->security->isGranted($aclToCheck.'create')) {
                return $this->accessDenied();
            }

            /** @var \Mautic\EmailBundle\Entity\Email|\Mautic\PageBundle\Entity\Page $entity */
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            /** @var \Mautic\EmailBundle\Entity\Email|\Mautic\PageBundle\Entity\Page $entity */
            $entity = $model->getEntity($objectId);
            $isNew  = false;

            if (null == $entity
                || !$this->security->hasEntityAccess(
                    $aclToCheck.'viewown',
                    $aclToCheck.'viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }
        }

        $slots        = [];
        $type         = 'html';
        $template     = InputHelper::clean($request->query->get('template'));
        if (!$template) {
            $mauticLogger->warning('Grapesjs: no template in query');

            return $this->json(false);
        }
        $templateName = '@themes/'.$template.'/html/'.$objectType;
        $content      = $entity->getContent();

        // Check for MJML template
        // @deprecated - use mjml directly in email.html.twig
        if ($logicalName = $this->checkForMjmlTemplate($templateName.'.mjml.twig')) {
            $type        = 'mjml';
        } else {
            $logicalName = $themeHelper->checkForTwigTemplate($templateName.'.html.twig');
            $slots       = $themeHelper->getTheme($template)->getSlots($objectType);

            // merge any existing changes
            $newContent = $request->getSession()->get('mautic.'.$objectType.'builder.'.$objectId.'.content', []);

            if (is_array($newContent)) {
                $content = array_merge($content, $newContent);
                // Update the content for processSlots
                $entity->setContent($content);
            }

            if ('page' === $objectType) {
                $this->processPageSlots($assetsHelper, $slotsHelper, $formFactory, $slots, $entity);
            } else {
                $this->processEmailSlots($slotsHelper, $slots, $entity);
            }
        }

        // Replace short codes to emoji
        $content = array_map(fn ($text) => EmojiHelper::toEmoji($text, 'short'), $content);

        $renderedTemplate =  $this->renderView(
            $logicalName,
            [
                'isNew'     => $isNew,
                'slots'     => $slots,
                'content'   => $content,
                $objectType => $entity,
                'template'  => $template,
                'basePath'  => $request->getBasePath(),
            ]
        );

        if (str_contains($renderedTemplate, '<mjml>')) {
            $type = 'mjml';
        }

        $renderedTemplateHtml = ('html' === $type) ? $renderedTemplate : '';
        $renderedTemplateMjml = ('mjml' === $type) ? $renderedTemplate : '';

        return $this->render(
            '@GrapesJsBuilder/Builder/template.html.twig',
            [
                'templateHtml' => $renderedTemplateHtml,
                'templateMjml' => $renderedTemplateMjml,
            ]
        );
    }

    /**
     * PreProcess email slots for public view.
     *
     * @param array $slots
     * @param Email $entity
     */
    private function processEmailSlots(SlotsHelper $slotsHelper, $slots, $entity): void
    {
        $content = $entity->getContent();

        // Set the slots
        foreach ($slots as $slot => $slotConfig) {
            // support previous format where email slots are not defined with config array
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            $value = $content[$slot] ?? '';
            $slotsHelper->set($slot, "<div data-slot=\"text\" id=\"slot-{$slot}\">{$value}</div>");
        }

        // add builder toolbar
        $slotsHelper->start('builder'); ?>
        <input type="hidden" id="builder_entity_id" value="<?php echo $entity->getSessionId(); ?>"/>
        <?php
        $slotsHelper->stop();
    }

    /**
     * PreProcess page slots for public view.
     *
     * @param array $slots
     * @param Page  $entity
     */
    private function processPageSlots(AssetsHelper $assetsHelper, SlotsHelper $slotsHelper, FormFactoryInterface $formFactory, $slots, $entity): void
    {
        $slotsHelper->inBuilder(true);

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            // backward compatibility - if slotConfig array does not exist
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            // define default config if does not exist
            if (!isset($slotConfig['type'])) {
                $slotConfig['type'] = 'html';
            }

            if (!isset($slotConfig['placeholder'])) {
                $slotConfig['placeholder'] = 'mautic.page.builder.addcontent';
            }

            $value = $content[$slot] ?? '';

            if ('slideshow' == $slotConfig['type']) {
                if (isset($content[$slot])) {
                    $options = json_decode($content[$slot], true);
                } else {
                    $options = [
                        'width'            => '100%',
                        'height'           => '250px',
                        'background_color' => 'transparent',
                        'arrow_navigation' => false,
                        'dot_navigation'   => true,
                        'interval'         => 5000,
                        'pause'            => 'hover',
                        'wrap'             => true,
                        'keyboard'         => true,
                    ];
                }

                // Create sample slides for first time or if all slides were deleted
                if (empty($options['slides'])) {
                    $options['slides'] = [
                        [
                            'order'            => 0,
                            'background-image' => $assetsHelper->getOverridableUrl('images/mautic_logo_lb200.png'),
                            'captionheader'    => 'Caption 1',
                        ],
                        [
                            'order'            => 1,
                            'background-image' => $assetsHelper->getOverridableUrl('images/mautic_logo_db200.png'),
                            'captionheader'    => 'Caption 2',
                        ],
                    ];
                }

                // Order slides
                usort(
                    $options['slides'],
                    fn ($a, $b): int => strcmp($a['order'], $b['order'])
                );

                $options['slot']   = $slot;
                $options['public'] = false;

                // create config form
                $options['configForm'] = $formFactory->createNamedBuilder(
                    '',
                    'slideshow_config',
                    [],
                    ['data' => $options]
                )->getForm()->createView();

                // create slide config forms
                foreach ($options['slides'] as $key => &$slide) {
                    $slide['key']  = $key;
                    $slide['slot'] = $slot;
                    $slide['form'] = $formFactory->createNamedBuilder(
                        '',
                        'slideshow_slide_config',
                        [],
                        ['data' => $slide]
                    )->getForm()->createView();
                }
            } else {
                $slotsHelper->set($slot, "<div data-slot=\"text\" id=\"slot-{$slot}\">{$value}</div>");
            }
        }

        $slotsHelper->start('builder'); ?>
        <input type="hidden" id="builder_entity_id" value="<?php echo $entity->getSessionId(); ?>"/>
        <?php
        $slotsHelper->stop();
    }

    /**
     * @deprecated deprecated since version 5.0 - use mjml directly in email.html.twig
     */
    private function checkForMjmlTemplate($template)
    {
        $twig = $this->get('twig');

        if ($twig->getLoader()->exists($template)) {
            return $template;
        }

        return null;
    }
}
