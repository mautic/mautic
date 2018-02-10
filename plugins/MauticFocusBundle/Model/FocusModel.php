<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Event\FocusEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class FocusModel extends FormModel
{
    /**
     * @var ContainerAwareEventDispatcher
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
     * @var
     */
    protected $leadModel;

    /**
     * FocusModel constructor.
     *
     * @param \Mautic\FormBundle\Model\FormModel $formModel
     * @param TrackableModel                     $trackableModel
     * @param TemplatingHelper                   $templating
     * @param EventDispatcherInterface           $dispatcher
     * @param LeadModel                          $leadModel
     */
    public function __construct(\Mautic\FormBundle\Model\FormModel $formModel, TrackableModel $trackableModel, TemplatingHelper $templating, EventDispatcherInterface $dispatcher, LeadModel $leadModel)
    {
        $this->formModel      = $formModel;
        $this->trackableModel = $trackableModel;
        $this->templating     = $templating;
        $this->dispatcher     = $dispatcher;
        $this->leadModel      = $leadModel;
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
        return 'plugin:focus:items';
    }

    /**
     * {@inheritdoc}
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
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

        return $formFactory->create('focus', $entity, $options);
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
        if ($id === null) {
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
     * @param Focus $focus
     * @param bool  $preview
     *
     * @return string
     */
    public function generateJavascript(Focus $focus, $isPreview = false, $byPassCache = false)
    {
        // If cached is not an array, rebuild to support the new format
        $cached = json_decode($focus->getCache(), true);
        if ($isPreview || $byPassCache || empty($cached) || !isset($cached['js'])) {
            $focusArray = $focus->toArray();

            $url = '';
            if ($focusArray['type'] == 'link' && !empty($focusArray['properties']['content']['link_url'])) {
                $trackable = $this->trackableModel->getTrackableByUrl(
                    $focusArray['properties']['content']['link_url'],
                    'focus',
                    $focusArray['id']
                );

                $url = $this->trackableModel->generateTrackableUrl(
                    $trackable,
                    ['channel' => ['focus', $focusArray['id']]],
                    false,
                    $focus->getUtmTags()
                );
            }

            $javascript = $this->templating->getTemplating()->render(
                'MauticFocusBundle:Builder:generate.js.php',
                [
                    'focus'    => $focusArray,
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
        $lead       = $this->leadModel->getCurrentLead();
        $tokenEvent = new TokenReplacementEvent($cached['focus'], $lead, ['focus_id' => $focus->getId()]);
        $this->dispatcher->dispatch(FocusEvents::TOKEN_REPLACEMENT, $tokenEvent);
        $focusContent = $tokenEvent->getContent();
        $focusContent = str_replace('{focus_form}', $cached['form'], $focusContent, $formReplaced);
        if (!$formReplaced && !empty($cached['form'])) {
            // Form token missing so just append the form
            $focusContent .= $cached['form'];
        }

        $focusContent = $this->templating->getTemplating()->getEngine('MauticFocusBundle:Builder:content.html.php')->escape($focusContent, 'js');

        return str_replace('{focus_content}', $focusContent, $cached['js']);
    }

    /**
     * @param array  $focus
     * @param bool   $isPreview
     * @param string $url
     *
     * @return array
     */
    public function getContent(array $focus, $isPreview = false, $url = '#')
    {
        $form = (!empty($focus['form'])) ? $this->formModel->getEntity($focus['form']) : null;

        if (isset($focus['html_mode'])) {
            $htmlMode = $focus['html_mode'];
        } elseif (isset($focus['htmlMode'])) {
            $htmlMode = $focus['htmlMode'];
        } else {
            $htmlMode = 'basic';
        }

        $content = $this->templating->getTemplating()->render(
            'MauticFocusBundle:Builder:content.html.php',
            [
                'focus'    => $focus,
                'preview'  => $isPreview,
                'htmlMode' => $htmlMode,
                'clickUrl' => $url,
            ]
        );

        // Form has to be generated outside of the content or else the form src will be converted to clickables
        $formContent = (!empty($form)) ? $this->templating->getTemplating()->render(
            'MauticFocusBundle:Builder:form.html.php',
            [
                'form'    => $form,
                'style'   => $focus['style'],
                'focusId' => $focus['id'],
                'preview' => $isPreview,
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
     * @param Focus $focus
     * @param       $type
     * @param null  $data
     * @param null  $lead
     */
    public function addStat(Focus $focus, $type, $data = null, $lead = null)
    {
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
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|FocusEvent|void
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Focus          $focus
     * @param                $unit
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null           $dateFormat
     * @param bool           $canViewOthers
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

        if ($focus->getType() != 'notification') {
            if ($focus->getType() == 'link') {
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
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder $q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'focus', 'm', 'e.id = t.focus_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }
}
