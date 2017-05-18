<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\Progress;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends FormController
{
    // Steps of the import
    const STEP_UPLOAD_CSV      = 1;
    const STEP_MATCH_FIELDS    = 2;
    const STEP_PROGRESS_BAR    = 3;
    const STEP_IMPORT_FROM_CSV = 4;

    /**
     * @param int $page
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction($page = 1)
    {
        return $this->indexStandard($page);
    }

    /**
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        return $this->viewStandard($objectId, 'lead', 'import');
    }

    /**
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction($objectId = 0, $ignorePost = false)
    {
        //Auto detect line endings for the file to work around MS DOS vs Unix new line characters
        ini_set('auto_detect_line_endings', true);

        /** @var \Mautic\LeadBundle\Model\ImportModel $importModel */
        $importModel = $this->getModel($this->getModelName());

        $session = $this->get('session');

        if (!$this->get('mautic.security')->isGranted('lead:imports:create')) {
            return $this->accessDenied();
        }

        // Move the file to cache and rename it
        $forceStop = $this->request->get('cancel', false);
        $step      = ($forceStop) ? 1 : $session->get('mautic.lead.import.step', self::STEP_UPLOAD_CSV);
        $fileName  = 'import.csv';
        $importDir = $this->getImportDirName();
        $fullPath  = $importDir.'/'.$fileName;
        $fs        = new Filesystem();
        $importId  = $session->get('mautic.lead.import.id');
        $complete  = false;

        if (!file_exists($fullPath)) {
            // Force step one if the file doesn't exist
            $step = 1;
            $session->set('mautic.lead.import.step', self::STEP_UPLOAD_CSV);
        }

        $progress = (new Progress())->bindArray($session->get('mautic.lead.import.progress', [0, 0]));
        $stats    = $session->get('mautic.lead.import.stats', ['merged' => 0, 'created' => 0, 'ignored' => 0, 'failures' => []]);
        $action   = $this->generateUrl('mautic_contact_import_action', ['objectAction' => 'new']);

        switch ($step) {
            case self::STEP_UPLOAD_CSV:

                if ($forceStop) {
                    $this->resetImport($fullPath);
                }

                $form = $this->get('form.factory')->create('lead_import', [], ['action' => $action]);
                break;
            case self::STEP_MATCH_FIELDS:

                /** @var \Mautic\LeadBundle\Model\FieldModel $pluginModel */
                $fieldModel = $this->getModel('lead.field');

                $leadFields   = $fieldModel->getFieldList(false, false);
                $importFields = $session->get('mautic.lead.import.importfields', []);

                $form = $this->get('form.factory')->create(
                    'lead_field_import',
                    [],
                    [
                        'action'        => $action,
                        'lead_fields'   => $leadFields,
                        'import_fields' => $importFields,
                    ]
                );

                break;
            case self::STEP_PROGRESS_BAR:
                // Just show the progress form
                $session->set('mautic.lead.import.step', self::STEP_IMPORT_FROM_CSV);
                break;

            case self::STEP_IMPORT_FROM_CSV:
                ignore_user_abort(true);

                $inProgress = $session->get('mautic.lead.import.inprogress', false);
                $checks     = $session->get('mautic.lead.import.progresschecks', 1);
                if (true || !$inProgress || $checks > 5) {
                    $session->set('mautic.lead.import.inprogress', true);
                    $session->set('mautic.lead.import.progresschecks', 1);

                    $import = $importModel->getEntity()
                        ->populateStats($stats)
                        ->setDir($importDir)
                        ->setFile($fileName)
                        ->setMatchedFields($session->get('mautic.lead.import.fields', []))
                        ->setDefault('owner', $session->get('mautic.lead.import.defaultowner', null))
                        ->setDefault('list', $session->get('mautic.lead.import.defaultlist', null))
                        ->setDefault('tags', $session->get('mautic.lead.import.defaulttags', null))
                        ->setHeaders($session->get('mautic.lead.import.headers', []))
                        ->setParserConfig($session->get('mautic.lead.import.config'));

                    $importModel->process(
                        $import,
                        $progress
                    );

                    $session->set('mautic.lead.import.stats', $import->getStats());

                    // Clear in progress
                    if ($progress->isFinished()) {
                        $this->resetImport($fullPath);
                        $complete = true;
                    } else {
                        $complete = false;
                        $session->set('mautic.lead.import.inprogress', false);
                        $session->set('mautic.lead.import.progress', $progress->toArray());
                    }

                    break;
                } else {
                    ++$checks;
                    $session->set('mautic.lead.import.progresschecks', $checks);
                }
        }

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                switch ($step) {
                    case self::STEP_UPLOAD_CSV:
                        if ($valid) {
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                            }

                            $fileData = $form['file']->getData();
                            if (!empty($fileData)) {
                                $errorMessage    = null;
                                $errorParameters = [];
                                try {
                                    // Create the import dir recursively
                                    $fs->mkdir($importDir, 0755);

                                    $fileData->move($importDir, $fileName);

                                    $file = new \SplFileObject($fullPath);

                                    $config = $form->getData();
                                    unset($config['file']);
                                    unset($config['start']);

                                    foreach ($config as $key => &$c) {
                                        $c = htmlspecialchars_decode($c);

                                        if ($key == 'batchlimit') {
                                            $c = (int) $c;
                                        }
                                    }

                                    $session->set('mautic.lead.import.config', $config);

                                    if ($file !== false) {
                                        // Get the headers for matching
                                        $headers = $file->fgetcsv($config['delimiter'], $config['enclosure'], $config['escape']);

                                        // Get the number of lines so we can track progress
                                        $file->seek(PHP_INT_MAX);
                                        $linecount = $file->key();
                                        $session->set('mautic.lead.import.linecount', $linecount);

                                        if (!empty($headers) && is_array($headers)) {
                                            array_walk($headers, create_function('&$val', '$val = trim($val);'));
                                            $session->set('mautic.lead.import.headers', $headers);
                                            sort($headers);
                                            $headers = array_combine($headers, $headers);

                                            $session->set('mautic.lead.import.step', self::STEP_MATCH_FIELDS);
                                            $session->set('mautic.lead.import.importfields', $headers);
                                            $session->set('mautic.lead.import.progress', [0, $linecount]);

                                            /** @var \Mautic\LeadBundle\Entity\Import $import */
                                            $import = $importModel->getEntity();

                                            $import->setDir($importDir)
                                                ->setLineCount($linecount)
                                                ->setFile($fileName)
                                                ->setOriginalFile($fileData->getClientOriginalName());

                                            $importModel->saveEntity($import);

                                            $session->set('mautic.lead.import.id', $import->getId());

                                            return $this->newAction(0, true);
                                        }
                                    }
                                } catch (FileException $e) {
                                    if (strpos($e->getMessage(), 'upload_max_filesize') !== false) {
                                        $errorMessage    = 'mautic.lead.import.filetoolarge';
                                        $errorParameters = [
                                            '%upload_max_filesize%' => ini_get('upload_max_filesize'),
                                        ];
                                    } else {
                                        $errorMessage = 'mautic.lead.import.filenotreadable';
                                    }
                                } catch (\Exception $e) {
                                    $errorMessage = 'mautic.lead.import.filenotreadable';
                                } finally {
                                    if (!is_null($errorMessage)) {
                                        $form->addError(
                                            new FormError(
                                                $this->get('translator')->trans($errorMessage, $errorParameters, 'validators')
                                            )
                                        );
                                    }
                                }
                            }
                        }
                        break;
                    case self::STEP_MATCH_FIELDS:
                        // Save matched fields
                        $matchedFields = $form->getData();

                        if (empty($matchedFields)) {
                            $this->resetImport($fullPath);

                            return $this->newAction(0, true);
                        }

                        $owner = $matchedFields['owner'];
                        unset($matchedFields['owner']);

                        $list = $matchedFields['list'];
                        unset($matchedFields['list']);

                        $tagCollection = $matchedFields['tags'];
                        $tags          = [];
                        foreach ($tagCollection as $tag) {
                            $tags[] = $tag->getTag();
                        }
                        unset($matchedFields['tags']);

                        foreach ($matchedFields as $k => $f) {
                            if (empty($f)) {
                                unset($matchedFields[$k]);
                            } else {
                                $matchedFields[$k] = trim($matchedFields[$k]);
                            }
                        }

                        if (empty($matchedFields)) {
                            $form->addError(
                                new FormError(
                                    $this->get('translator')->trans('mautic.lead.import.matchfields', [], 'validators')
                                )
                            );
                        } else {
                            $defaultOwner = ($owner) ? $owner->getId() : null;

                            if ($form->get('buttons')->get('apply')->isClicked()) {

                                /** @var \Mautic\LeadBundle\Entity\Import $import */
                                $import = $importModel->getEntity($importId);

                                $import->setMatchedFields($matchedFields)
                                    ->setDefault('owner', $defaultOwner)
                                    ->setDefault('list', $list)
                                    ->setDefault('tags', $tags)
                                    ->setHeaders($session->get('mautic.lead.import.headers'))
                                    ->setParserConfig($session->get('mautic.lead.import.config'));

                                $importModel->saveEntity($import);

                                try {
                                    $this->addFlash('mautic.lead.batch.import.created');
                                    $this->resetImport($fullPath, false);
                                    $step = self::STEP_UPLOAD_CSV;
                                } catch (Exception $e) {
                                    $errorMessage = 'mautic.lead.import.filenotreadable';
                                }
                            } else {
                                $session->set('mautic.lead.import.fields', $matchedFields);
                                $session->set('mautic.lead.import.defaultowner', $defaultOwner);
                                $session->set('mautic.lead.import.defaultlist', $list);
                                $session->set('mautic.lead.import.defaulttags', $tags);
                                $session->set('mautic.lead.import.step', self::STEP_PROGRESS_BAR);
                            }

                            return $this->newAction(0, true);
                        }
                        break;

                    default:
                        // Done or something wrong

                        $this->resetImport($fullPath);

                        break;
                }
            } else {
                $this->resetImport($fullPath);

                return $this->newAction(0, true);
            }
        }

        if ($step === self::STEP_UPLOAD_CSV || $step === self::STEP_MATCH_FIELDS) {
            $contentTemplate = 'MauticLeadBundle:Import:new.html.php';
            $viewParameters  = ['form' => $form->createView()];
        } else {
            $contentTemplate = 'MauticLeadBundle:Import:progress.html.php';
            $viewParameters  = [
                'progress' => $progress,
                'stats'    => $stats,
                'complete' => $complete,
            ];
        }

        if (!$complete && $this->request->query->has('importbatch')) {
            // Ajax request to batch process so just return ajax response unless complete

            return new JsonResponse(['success' => 1, 'ignore_wdt' => 1]);
        } else {
            return $this->delegateView(
                [
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $contentTemplate,
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_contact_index',
                        'mauticContent' => 'leadImport',
                        'route'         => $this->generateUrl(
                            'mautic_contact_import_action',
                            [
                                'objectAction' => 'new',
                            ]
                        ),
                        'step'     => $step,
                        'progress' => $progress,
                    ],
                ]
            );
        }
    }

    /**
     * Generates unique import directory name inside the cache dir if not stored in the session.
     * If it exists in the session, returns that one.
     *
     * @return string
     */
    protected function getImportDirName()
    {
        $session = $this->get('session');

        // Return the dir path from session if exists
        if ($importDir = $session->get('mautic.lead.import.dir')) {
            return $importDir;
        }

        /** @var \Mautic\LeadBundle\Model\ImportModel $importModel */
        $importModel = $this->getModel('lead.import');

        $importDir = $importModel->getUniqueDir();

        // Set the dir path to session
        $session->set('mautic.lead.import.dir', $importDir);

        return $importDir;
    }

    /**
     * @param $filepath
     */
    private function resetImport($filepath, $removeCsv = true)
    {
        $session = $this->get('session');
        $session->set('mautic.lead.import.stats', ['merged' => 0, 'created' => 0, 'ignored' => 0]);
        $session->set('mautic.lead.import.headers', []);
        $session->set('mautic.lead.import.dir', null);
        $session->set('mautic.lead.import.step', self::STEP_UPLOAD_CSV);
        $session->set('mautic.lead.import.progress', [0, 0]);
        $session->set('mautic.lead.import.fields', []);
        $session->set('mautic.lead.import.defaultowner', null);
        $session->set('mautic.lead.import.defaultlist', null);
        $session->set('mautic.lead.import.inprogress', false);
        $session->set('mautic.lead.import.importfields', []);
        $session->set('mautic.lead.import.linecount', null);

        if ($removeCsv) {
            unlink($filepath);
        }
    }

    /**
     * @param array $args
     * @param       $action
     *
     * @return array
     */
    public function getViewArguments(array $args, $action)
    {
        switch ($action) {
            case 'view':
                /** @var Import $entity */
                $entity = $args['entity'];

                /** @var \Mautic\LeadBundle\Model\ImportModel $model */
                $model = $this->getModel($this->getModelName());

                /** @var LeadEventLogRepository $eventLogRepo */
                $eventLogRepo = $this->getDoctrine()->getManager()->getRepository('MauticLeadBundle:LeadEventLog');

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                        'failedRows' => $eventLogRepo->getFailedRows($entity->getId(), ['select' => 'properties,id']),
                    ]
                );

                break;
        }

        return $args;
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelName()
    {
        return 'lead.import';
    }

    /***
     * @param null $objectId
     *
     * @return string
     */
    protected function getSessionBase($objectId = null)
    {
        return 'lead.import'.(($objectId) ? '.'.$objectId : '');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionBase()
    {
        return $this->getModel($this->getModelName())->getPermissionBase();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteBase()
    {
        return 'contact_import';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getTemplateBase()
    {
        return 'MauticLeadBundle:Import';
    }

    /**
     * Provide the name of the column which is used for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderColumn()
    {
        return 'dateAdded';
    }

    /**
     * Provide the direction for default ordering.
     *
     * @return string
     */
    protected function getDefaultOrderDirection()
    {
        return 'DESC';
    }
}
