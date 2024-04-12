<?php

namespace Mautic\CoreBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<object>
 */
class FileApiController extends CommonApiController
{
    /**
     * Holds array of allowed file extensions.
     *
     * @var array
     */
    protected $allowedExtensions = [];

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper, MauticFactory $factory)
    {
        $this->entityNameOne     = 'file';
        $this->entityNameMulti   = 'files';
        $this->allowedExtensions = $coreParametersHelper->get('allowed_extensions');

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
    }

    /**
     * Uploads a file.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request, PathsHelper $pathsHelper, LoggerInterface $mauticLogger, $dir)
    {
        try {
            $path = $this->getAbsolutePath($request, $pathsHelper, $mauticLogger, $dir, true);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_NOT_ACCEPTABLE);
        }

        $response = [$this->entityNameOne => []];
        if ($request->files) {
            foreach ($request->files as $file) {
                $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
                if (in_array($extension, $this->allowedExtensions)) {
                    $fileName = md5(uniqid()).'.'.$extension;
                    $moved    = $file->move($path, $fileName);

                    if (str_starts_with($dir, 'images')) {
                        $response[$this->entityNameOne]['link'] = $this->getMediaUrl($request).'/'.$fileName;
                    }

                    $response[$this->entityNameOne]['name'] = $fileName;
                } else {
                    return $this->returnError('The uploaded file can have only these extensions: '.implode(',', $this->allowedExtensions).'.', Response::HTTP_NOT_ACCEPTABLE);
                }
            }
        } else {
            return $this->returnError('File was not found in the request.', Response::HTTP_NOT_ACCEPTABLE);
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }

    /**
     * List the files in /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, PathsHelper $pathsHelper, LoggerInterface $mauticLogger, $dir)
    {
        try {
            $filePath = $this->getAbsolutePath($request, $pathsHelper, $mauticLogger, $dir);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_NOT_ACCEPTABLE);
        }

        $fnames = scandir($filePath);

        if (is_array($fnames)) {
            foreach ($fnames as $key => $name) {
                // remove hidden files
                if (str_starts_with($name, '.')) {
                    unset($fnames[$key]);
                }
            }
        } else {
            return $this->returnError(ucfirst($dir).' dir is not readable');
        }

        $view = $this->view([$this->entityNameMulti => $fnames]);

        return $this->handleView($view);
    }

    /**
     * Delete a file from /media directory.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, PathsHelper $pathsHelper, LoggerInterface $mauticLogger, $dir, $file)
    {
        $response = ['success' => false];

        try {
            $filePath = $this->getAbsolutePath($request, $pathsHelper, $mauticLogger, $dir).'/'.basename($file);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_NOT_ACCEPTABLE);
        }

        if (!file_exists($filePath)) {
            return $this->returnError('File does not exist', Response::HTTP_NOT_FOUND);
        } elseif (!is_writable($filePath)) {
            return $this->returnError('File is not writable');
        } else {
            unlink($filePath);
            $response['success'] = true;
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }

    /**
     * Get the Media directory full file system path.
     *
     * @param string $dir
     * @param bool   $createDir
     *
     * @return string
     */
    protected function getAbsolutePath(Request $request, PathsHelper $pathsHelper, LoggerInterface $mauticLogger, $dir, $createDir = false)
    {
        try {
            $possibleDirs = ['media', 'images'];
            $dir          = InputHelper::alphanum($dir, true, null, ['_', '.']);
            $subdir       = trim(InputHelper::alphanum($request->get('subdir', ''), true, null, ['/']));

            // Dots in the dir name are slashes
            if (str_contains($dir, '.') && !$subdir) {
                $dirs = explode('.', $dir);
                $dir  = $dirs[0];
                unset($dirs[0]);
                $subdir = implode('/', $dirs);
            }

            if (!in_array($dir, $possibleDirs)) {
                throw new \InvalidArgumentException($dir.' not found. Only '.implode(' or ', $possibleDirs).' options are possible.');
            }

            if ('images' === $dir) {
                $absoluteDir = realpath($pathsHelper->getSystemPath($dir, true));
            } elseif ('media' === $dir) {
                $absoluteDir = realpath($this->coreParametersHelper->get('upload_dir'));
            }

            if (false === $absoluteDir) {
                throw new \InvalidArgumentException($dir.' dir does not exist', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if (false === is_writable($absoluteDir)) {
                throw new \InvalidArgumentException($dir.' dir is not writable', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $path = $absoluteDir.'/'.$subdir;

            if (!file_exists($path)) {
                if ($createDir) {
                    if (false === mkdir($path)) {
                        throw new \InvalidArgumentException($dir.'/'.$subdir.' subdirectory could not be created.', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                } else {
                    throw new \InvalidArgumentException($subdir.' doesn\'t exist in the '.$dir.' dir.');
                }
            }

            return $path;
        } catch (\Exception $e) {
            $mauticLogger->error($e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Get the Media directory full file system path.
     */
    protected function getMediaUrl(Request $request): string
    {
        return $request->getScheme().'://'
            .$request->getHttpHost()
            .':'.$request->getPort()
            .$request->getBasePath().'/'
            .$this->coreParametersHelper->get('image_path');
    }
}
