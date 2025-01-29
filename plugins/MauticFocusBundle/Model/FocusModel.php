<?php

namespace MauticPlugin\MauticFocusBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use MatthiasMullie\Minify;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\ProgressiveProfiling\DisplayManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Event\FocusEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Form\Type\FocusType;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;

/**
 * @extends FormModel<Focus>
 */
class FocusModel extends FormModel implements GlobalSearchInterface
{
    public function __construct(
        protected \Mautic\FormBundle\Model\FormModel $formModel,
        protected TrackableModel $trackableModel,
        protected Environment $twig,
        protected FieldModel $leadFieldModel,
        protected ContactTracker $contactTracker,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $this->dispatcher     = $dispatcher;

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getActionRouteBase(): string
    {
        return 'focus';
    }

    public function getPermissionBase(): string
    {
        return 'focus:items';
    }

    /**
     * @param object      $entity
     * @param string|null $action
     * @param array       $options
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Focus) {
            throw new MethodNotAllowedHttpException(['Focus']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(FocusType::class, $entity, $options);
    }

    /**
     * @return \MauticPlugin\MauticFocusBundle\Entity\FocusRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Focus::class);
    }

    /**
     * @return \MauticPlugin\MauticFocusBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository(Stat::class);
    }

    /**
     * @param int|null $id
     */
    public function getEntity($id = null): ?Focus
    {
        if (null === $id) {
            return new Focus();
        }

        return parent::getEntity($id);
    }

    /**
     * @param Focus      $entity
     * @param bool|false $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        parent::saveEntity($entity, $unlock);

        // Generate cache after save to have ID available
        $content = $this->generateJavascript($entity);
        $entity->setCache($content);

        $this->getRepository()->saveEntity($entity);
    }

    /**
     * @return string|string[]
     */
    public function generateJavascript(Focus $focus, $isPreview = false, $byPassCache = false): array|string
    {
        // If cached is not an array, rebuild to support the new format
        $cached = $focus->getCache() ? json_decode($focus->getCache(), true) : [];
        if ($isPreview || $byPassCache || empty($cached) || !isset($cached['js'])) {
            $focusArray = $focus->toArray();

            $url = '';
            if ('link' == $focusArray['type'] && !empty($focusArray['properties']['content']['link_url'])) {
                $url = $focusArray['properties']['content']['link_url'];
            }

            $javascript = $this->twig->render(
                '@MauticFocus/Builder/generate.js.twig',
                [
                    'focus'    => $focus,
                    'preview'  => $isPreview,
                    'clickUrl' => $url,
                ]
            );

            $content = $this->getContent($focusArray, $isPreview, $url);
            $cached  = [
                'js'    => (new Minify\JS($javascript))->minify(),
                'focus' => InputHelper::minifyHTML($content['focus']),
                'form'  => InputHelper::minifyHTML($content['form']),
            ];

            if (!$byPassCache) {
                $focus->setCache(json_encode($cached));
                $this->saveEntity($focus);
            }
        }

        // Replace tokens to ensure clickthroughs, lead tokens etc are appropriate
        $lead       = $this->contactTracker->getContact();
        $tokenEvent = new TokenReplacementEvent($cached['focus'], $lead, ['focus_id' => $focus->getId()]);
        $this->dispatcher->dispatch($tokenEvent, FocusEvents::TOKEN_REPLACEMENT);
        $focusContent = $tokenEvent->getContent();

        $focusContent = str_replace('{focus_form}', $cached['form'], $focusContent, $formReplaced);
        if (!$formReplaced && !empty($cached['form'])) {
            // Form token missing so just append the form
            $focusContent .= $cached['form'];
        }

        $focusContent = $this->twig->getRuntime(EscaperRuntime::class)->escape($focusContent, 'js');

        return str_replace('{focus_content}', $focusContent, $cached['js']);
    }

    /**
     * @param bool   $isPreview
     * @param string $url
     *
     * @return array
     */
    public function getContent(array $focus, $isPreview = false, $url = '#')
    {
        $form = (!empty($focus['form']) && 'form' === $focus['type']) ? $this->formModel->getEntity($focus['form']) : null;

        if (isset($focus['html_mode'])) {
            $htmlMode = $focus['htmlMode'] = $focus['html_mode'];
        } elseif (isset($focus['htmlMode'])) {
            $htmlMode = $focus['htmlMode'];
        } else {
            $htmlMode = 'basic';
        }

        if (isset($focus[$htmlMode])) {
            $focus[$htmlMode] = htmlspecialchars_decode($focus[$htmlMode]);
        }

        $content = $this->twig->render(
            '@MauticFocus/Builder/content.html.twig',
            [
                'focus'    => $focus,
                'preview'  => $isPreview,
                'htmlMode' => $htmlMode,
                'clickUrl' => $url,
            ]
        );

        // Form has to be generated outside of the content or else the form src
        // will be converted to clickables
        $fields             = $form ? $form->getFields()->toArray() : [];
        [$pages, $lastPage] = $this->formModel->getPages($fields);
        $displayManager     = $viewOnlyFields = null;
        if ($form) {
            $viewOnlyFields = $this->formModel->getCustomComponents()['viewOnlyFields'];
            $displayManager = new DisplayManager($form, !empty($viewOnlyFields) ? $viewOnlyFields : []);
        }
        $formContent        = (!empty($form)) ? $this->twig->render(
            '@MauticFocus/Builder/form.html.twig',
            [
                'form'           => $form,
                'pages'          => $pages,
                'lastPage'       => $lastPage,
                'style'          => $focus['style'],
                'focusId'        => $focus['id'],
                'preview'        => $isPreview,
                'contactFields'  => $this->leadFieldModel->getFieldListWithProperties(),
                'companyFields'  => $this->leadFieldModel->getFieldListWithProperties('company'),
                'viewOnlyFields' => $viewOnlyFields,
                'displayManager' => $displayManager,
            ]
        ) : '';

        if ($isPreview) {
            $content = str_replace('{focus_form}', $formContent, $content, $formReplaced);
            if (!$formReplaced && !empty($formContent)) {
                $content .= $formContent;
            }

            return $content;
        }

        return [
            'focus' => $content,
            'form'  => $formContent,
        ];
    }

    /**
     * Get whether the color is light or dark.
     */
    public static function isLightColor($hex, $level = 200): bool
    {
        $hex = str_replace('#', '', $hex);
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));

        $compareWith = ((($r * 299) + ($g * 587) + ($b * 114)) / 1000);

        return $compareWith >= $level;
    }

    /**
     * Add a stat entry.
     *
     * @param mixed                                                    $type
     * @param mixed                                                    $data
     * @param array<int|string|array<int|string>>|Lead|Submission|null $lead
     */
    public function addStat(Focus $focus, $type, $data = null, $lead = null): ?Stat
    {
        if (empty($lead)) {
            return null;
        }

        if ($lead instanceof Lead && !$lead->getId()) {
            return null;
        }

        if (is_array($lead)) {
            if (empty($lead['id'])) {
                return null;
            }

            $lead = $this->em->getReference(Lead::class, $lead['id']);
        }

        switch ($type) {
            case Stat::TYPE_FORM:
            case Stat::TYPE_CLICK:
                /** @var \Mautic\PageBundle\Entity\Hit|Submission $data */
                $typeId = $data->getId();
                break;
            case Stat::TYPE_NOTIFICATION:
                $typeId = null;
                break;
        }

        $stat = new Stat();
        $stat->setFocus($focus)
            ->setDateAdded(new \DateTime())
            ->setType($type)
            ->setTypeId($typeId)
            ->setLead($lead);

        $this->getStatRepository()->saveEntity($stat);

        return $stat;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Focus) {
            throw new MethodNotAllowedHttpException(['Focus']);
        }

        switch ($action) {
            case 'pre_save':
                $name = FocusEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = FocusEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = FocusEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = FocusEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FocusEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param bool $canViewOthers
     */
    public function getStats(Focus $focus, $unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $canViewOthers = true): array
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);

        $q = $query->prepareTimeDataQuery('focus_stats', 'date_added', ['type' => Stat::TYPE_NOTIFICATION, 'focus_id' => $focus->getId()]);
        if (!$canViewOthers) {
            $this->limitQueryToCreator($q);
        }
        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.focus.graph.views'), $data);

        if ('notification' != $focus->getType()) {
            if ('link' == $focus->getType()) {
                $q = $query->prepareTimeDataQuery('focus_stats', 'date_added', ['type' => Stat::TYPE_CLICK, 'focus_id' => $focus->getId()]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.focus.graph.clicks'), $data);
            } else {
                $q = $query->prepareTimeDataQuery('focus_stats', 'date_added', ['type' => Stat::TYPE_FORM, 'focus_id' => $focus->getId()]);
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                $chart->setDataset($this->translator->trans('mautic.focus.graph.submissions'), $data);
            }
        }

        return $chart->render();
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     */
    public function limitQueryToCreator(QueryBuilder $q): void
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'focus', 'm', 'e.id = t.focus_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    public function getViewsCount(Focus $focus): int
    {
        return $this->getStatRepository()->getViewsCount($focus->getId());
    }

    public function getUniqueViewsCount(Focus $focus): int
    {
        return $this->getStatRepository()->getUniqueViewsCount($focus->getId());
    }

    public function getClickThroughCount(Focus $focus): int
    {
        return $this->getStatRepository()->getClickThroughCount($focus->getId());
    }
}
