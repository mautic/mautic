<?php

namespace MauticPlugin\MauticFocusBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Focus>
 */
class FocusModel extends FormModel
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Mautic\FormBundle\Model\FormModel
     */
    protected $formModel;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var TemplatingHelper
     */
    protected $templating;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var ContactTracker
     */
    protected $contactTracker;

    /**
     * FocusModel constructor.
     */
    public function __construct(
        \Mautic\FormBundle\Model\FormModel $formModel,
        TrackableModel $trackableModel,
        TemplatingHelper $templating,
        EventDispatcherInterface $dispatcher,
        FieldModel $leadFieldModel,
        ContactTracker $contactTracker
    ) {
        $this->formModel      = $formModel;
        $this->trackableModel = $trackableModel;
        $this->templating     = $templating;
        $this->dispatcher     = $dispatcher;
        $this->leadFieldModel = $leadFieldModel;
        $this->contactTracker = $contactTracker;
    }

    /**
     * @return string
     */
    public function getActionRouteBase()
    {
        return 'focus';
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'focus:items';
    }

    /**
     * {@inheritdoc}
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
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
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticFocusBundle\Entity\FocusRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFocusBundle:Focus');
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticFocusBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticFocusBundle:Stat');
    }

    /**
     * {@inheritdoc}
     *
     * @param null $id
     *
     * @return Focus
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Focus();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param Focus      $entity
     * @param bool|false $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);

        // Generate cache after save to have ID available
        $content = $this->generateJavascript($entity);
        $entity->setCache($content);

        $this->getRepository()->saveEntity($entity);
    }

    /**
     * Returns a trackable URL for the focus passed in. It will return an empty
     * string if there is no url.
     */
    //private function getTrackableUrl(Focus $focus): string
    //{
    //    if ('link' == $focus->getType() && !empty($focus->getProperties()['content']['link_url'])) {
    //        $trackable = $this->trackableModel->getTrackableByUrl(
    //            $focus->getProperties()['content']['link_url'],
    //            'focus',
    //            $focus->getId()
    //        );

    //        return $this->trackableModel->generateTrackableUrl(
    //            $trackable,
    //            ['channel' => ['focus', $focus->getId()]],
    //            false,
    //            $focus->getUtmTags()
    //        );
    //    }

    //    return '';
    //}

    /**
     * @return string
     */
    public function generateJavascript(Focus $focus, $isPreview = false, $byPassCache = false)
    {
        // If cached is not an array, rebuild to support the new format
        $cached = json_decode($focus->getCache(), true);
        if ($isPreview || $byPassCache || empty($cached) || !isset($cached['js'])) {
            $focusArray = $focus->toArray();

            $url = '';
            if ('link' == $focus->getType() && !empty($focus->getProperties()['content']['link_url'])) {
                $trackable = $this->trackableModel->getTrackableByUrl(
                    $focus->getProperties()['content']['link_url'],
                    'focus',
                    $focus->getId()
                );

                return $this->trackableModel->generateTrackableUrl(
                    $trackable,
                    ['channel' => ['focus', $focus->getId()]],
                    false,
                    $focus->getUtmTags()
                );
            }

            $javascript = $this->templating->getTemplating()->render(
                'MauticFocusBundle:Builder:generate.js.twig',
                [
                    'focus'    => $focus,
                    'preview'  => $isPreview,
                    'clickUrl' => $url,
                ]
            );

            $content = $this->getContent($focusArray, $isPreview, $url);
            $cached  = [
                'js'    => \Minify_HTML::minify($javascript),
                'focus' => \Minify_HTML::minify($content['focus']),
                'form'  => \Minify_HTML::minify($content['form']),
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

        // NOTE: {focus_form} may appear in the focus->getEditor() or focus->getHtml() data
        $focusContent = str_replace('{focus_form}', $cached['form'], $focusContent, $formReplaced);

        if (!$formReplaced && !empty($cached['form'])) {
            // Form token missing so just append the form
            $focusContent .= $cached['form'];
        }

        // in twig, this is {{ focusContent|e('js') }}
        //$focusContent = $this->templating->getTemplating()->getEngine('MauticFocusBundle:Builder:content.html.twig')->escape($focusContent, 'js');

        return str_replace('{focus_content}', $focusContent, $cached['js']);
    }

    /**
     * Renders the focus item.
     */
    //public function renderContentHtml(Focus $entity, bool $isPreview = false, string $url = '#'): string
    //{
    //    return $this->templating->getTemplating()->render('MauticFocusBundle:Builder:content.html.twig', [
    //            'focus'    => $entity,
    //            'preview'  => $isPreview,
    //            'htmlMode' => $entity->getHtmlMode(), // @todo remove
    //            'clickUrl' => $url,
    //    ]);
    //}

    /**
     * This is used to generate `{focus_form}` raw html.
     *
     * @param \Mautic\FormBundle\Entity\Form $form
     */
    //public function renderFormHtml(
    //    Focus $entity,
    //    $form,
    //    bool $isPreview = false
    //): string {
    //    [$pages, $lastPage] = $this->formModel->getPages($form->getFields()->toArray());

    //    return $this->templating->getTemplating()->render('MauticFocusBundle:Builder:form.html.twig', [
    //        'form'           => $form,
    //        'pages'          => $pages,
    //        'lastPage'       => $lastPage,
    //        'style'          => $entity->getStyle(),
    //        'focusId'        => $entity->getId(),
    //        'preview'        => $isPreview,
    //        'contactFields'  => $this->leadFieldModel->getFieldListWithProperties(),
    //        'companyFields'  => $this->leadFieldModel->getFieldListWithProperties('company'),
    //        'viewOnlyFields' => $this->formModel->getCustomComponents()['viewOnlyFields'],
    //    ]);
    //}

    /**
     * @todo $focus should be Focus entity
     *
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

        //$content = $this->renderContentHtml($this->getEntity($focus['id']), $isPreview, $url);
        $content = $this->templating->getTemplating()->render(
            'MauticFocusBundle:Builder:content.html.twig',
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
        $displayManager     = $viewOnlyFields     = null;
        if ($form) {
            $viewOnlyFields = $this->formModel->getCustomComponents()['viewOnlyFields'];
            $displayManager = new DisplayManager($form, !empty($viewOnlyFields) ? $viewOnlyFields : []);
        }
        $formContent        = (!empty($form)) ? $this->templating->getTemplating()->render(
            'MauticFocusBundle:Builder:form.html.twig',
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
     *
     * @param $hex
     * @param $level
     *
     * @return bool
     */
    public static function isLightColor($hex, $level = 200)
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
     * @param mixed                                         $type
     * @param null                                          $data
     * @param array<int|string|array<int|string>>|Lead|null $lead
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
                /** @var \Mautic\FormBundle\Entity\Submission $data */
                $typeId = $data->getId();
                break;
            case Stat::TYPE_NOTIFICATION:
                /** @var Request $data */
                $typeId = null;
                break;
            case Stat::TYPE_CLICK:
                /** @var \Mautic\PageBundle\Entity\Hit $data */
                $typeId = $data->getId();
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
     * {@inheritdoc}
     *
     * @return Event|void|null
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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
     * @param      $unit
     * @param null $dateFormat
     * @param bool $canViewOthers
     *
     * @return array
     */
    public function getStats(Focus $focus, $unit, \DateTime $dateFrom = null, \DateTime $dateTo = null, $dateFormat = null, $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);

        $q = $query->prepareTimeDataQuery('focus_stats', 'date_added', ['focus_id' => $focus->getId()]);
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
    public function limitQueryToCreator(QueryBuilder $q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'focus', 'm', 'e.id = t.focus_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }
}
