<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

namespace Mautic\InstallBundle\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * InstallController.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InstallController extends CommonController
{
    /**
     * Controller action for install steps
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stepAction($index = 0)
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $action = $this->generateUrl('mautic_installer_step', array('index' => $index));
        $step   = $configurator->getStep($index);
        $form   = $this->container->get('form.factory')->create($step->getFormType(), $step, array('action' => $action));
        $tmpl   = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Always pass the requirements into the templates
        $majors = $configurator->getRequirements();
        $minors = $configurator->getOptionalSettings();

        $request = $this->container->get('request');
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $configurator->mergeParameters($step->update($form->getData()));

                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'    => $form->createView(),
                            'index'   => $index,
                            'count'   => $configurator->getStepCount(),
                            'version' => $this->factory->getVersion(),
                            'tmpl'    => $tmpl,
                            'majors'  => $majors,
                            'minors'  => $minors,
                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                        ),
                        'returnUrl'       => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                        'contentTemplate' => $step->getTemplate(),
                        'passthroughVars' => array(
                            'activeLink'    => '#mautic_installer_index',
                            'mauticContent' => 'installer'
                        ),
                        'flashes'         => array(
                            array(
                                'type'    => 'error',
                                'msg'     => 'mautic.installer.error.writing.configuration'
                            )
                        )
                    ));
                }

                // Post-step processing
                switch ($index) {
                    case 1:
                        $this->clearCache();

                        $entityManager = $this->factory->getEntityManager();
                        $metadatas     = $entityManager->getMetadataFactory()->getAllMetadata();

                        if (!empty($metadatas)) {
                            try {
                                $schemaTool = new SchemaTool($entityManager);
                                $schemaTool->createSchema($metadatas);
                            } catch (\Exception $exception) {
                                $error = false;
                                if (strpos($exception->getMessage(), $this->checkDatabaseNotExistsMessage($originalData->driver, $originalData->name)) !== false) {
                                    // Try to manually create the database, first we null out the database name
                                    $originalData   = $form->getData();
                                    $editData       = clone $originalData;
                                    $editData->name = null;
                                    $configurator->mergeParameters($step->update($editData));
                                    $configurator->write();
                                    $this->clearCache();
                                    try {
                                        $this->factory->getEntityManager()->getConnection()->executeQuery('CREATE DATABASE ' . $data->name);

                                        // Assuming we got here, we should be able to install correctly now
                                        $configurator->mergeParameters($step->update($originalData));
                                        $configurator->write();
                                        $this->clearCache();
                                        $schemaTool = new SchemaTool($entityManager);
                                        $schemaTool->createSchema($metadatas);
                                    } catch (\Exception $exception) {
                                        // We did our best, we really did
                                        $error = true;
                                        $msg   = 'mautic.installer.error.creating.database';
                                    }
                                } else {
                                    $error = true;
                                    if (strpos($exception->getMessage(), 'Base table or view already exists') !== false) {
                                        $msg = 'mautic.installer.error.database.exists';
                                    } else {
                                        $msg = 'mautic.installer.error.creating.database';
                                    }
                                }

                                if ($error) {
                                    return $this->postActionRedirect(array(
                                        'viewParameters'    => array(
                                            'form'    => $form->createView(),
                                            'index'   => $index,
                                            'count'   => $configurator->getStepCount(),
                                            'version' => $this->factory->getVersion(),
                                            'tmpl'    => $tmpl,
                                            'majors'  => $majors,
                                            'minors'  => $minors,
                                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                                        ),
                                        'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                                        'contentTemplate'   => $step->getTemplate(),
                                        'passthroughVars'   => array(
                                            'activeLink'    => '#mautic_installer_index',
                                            'mauticContent' => 'installer'
                                        ),
                                        'flashes'           => array(
                                            array(
                                                'type'    => 'error',
                                                'msg'     => $msg,
                                                'msgVars' => array('%exception%' => $exception->getMessage())
                                            )
                                        ),
                                        'forwardController' => false
                                    ));
                                }
                            }
                        } else {
                            return $this->postActionRedirect(array(
                                'viewParameters'    => array(
                                    'form'    => $form->createView(),
                                    'index'   => $index,
                                    'count'   => $configurator->getStepCount(),
                                    'version' => $this->factory->getVersion(),
                                    'tmpl'    => $tmpl,
                                    'majors'  => $majors,
                                    'minors'  => $minors,
                                    'appRoot' => $this->container->getParameter('kernel.root_dir'),
                                ),
                                'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                                'contentTemplate'   => $step->getTemplate(),
                                'passthroughVars'   => array(
                                    'activeLink'    => '#mautic_installer_index',
                                    'mauticContent' => 'installer'
                                ),
                                'flashes'           => array(
                                    array(
                                        'type' => 'error',
                                        'msg'  => 'mautic.installer.error.no.metadata'
                                    )
                                ),
                                'forwardController' => false
                            ));
                        }

                        break;

                    case 2:
                        try {
                            // First we need to create the admin role
                            $translator    = $this->factory->getTranslator();
                            $entityManager = $this->factory->getEntityManager();
                            $role = new Role();
                            $role->setName($translator->trans('mautic.user.role.admin.name', array(), 'fixtures'));
                            $role->setDescription($translator->trans('mautic.user.role.admin.description', array(), 'fixtures'));
                            $role->setIsAdmin(1);
                            $entityManager->persist($role);
                            $entityManager->flush();

                            // Now we create the user
                            $data = $form->getData();
                            $user = new User();

                            /** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
                            $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);

                            $user->setFirstName($data->firstname);
                            $user->setLastName($data->lastname);
                            $user->setUsername($data->username);
                            $user->setEmail($data->email);
                            $user->setPassword($encoder->encodePassword($data->password, $user->getSalt()));
                            $user->setRole($role);
                            $entityManager->persist($user);
                            $entityManager->flush();
                        } catch (\Exception $exception) {
                            return $this->postActionRedirect(array(
                                'viewParameters'    => array(
                                    'form'    => $form->createView(),
                                    'index'   => $index,
                                    'count'   => $configurator->getStepCount(),
                                    'version' => $this->factory->getVersion(),
                                    'tmpl'    => $tmpl,
                                    'majors'  => $majors,
                                    'minors'  => $minors,
                                    'appRoot' => $this->container->getParameter('kernel.root_dir'),
                                ),
                                'returnUrl'         => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                                'contentTemplate'   => $step->getTemplate(),
                                'passthroughVars'   => array(
                                    'activeLink'    => '#mautic_installer_index',
                                    'mauticContent' => 'installer'
                                ),
                                'flashes'           => array(
                                    array(
                                        'type'    => 'error',
                                        'msg'     => 'mautic.installer.error.creating.user',
                                        'msgVars' => array('%exception%' => $exception->getMessage())
                                    )
                                ),
                                'forwardController' => false
                            ));
                        }

                        break;
                }

                $index++;

                if ($index < $configurator->getStepCount()) {
                    $nextStep = $configurator->getStep($index);
                    $action   = $this->generateUrl('mautic_installer_step', array('index' => $index));

                    $form = $this->container->get('form.factory')->create($nextStep->getFormType(), $nextStep, array('action' => $action));

                    return $this->postActionRedirect(array(
                        'viewParameters'    => array(
                            'form'    => $form->createView(),
                            'index'   => $index,
                            'count'   => $configurator->getStepCount(),
                            'version' => $this->factory->getVersion(),
                            'tmpl'    => $tmpl,
                            'majors'  => $majors,
                            'minors'  => $minors,
                            'appRoot' => $this->container->getParameter('kernel.root_dir'),
                        ),
                        'returnUrl'         => $action,
                        'contentTemplate'   => $nextStep->getTemplate(),
                        'passthroughVars'   => array(
                            'activeLink'    => '#mautic_installer_index',
                            'mauticContent' => 'installer'
                        ),
                        'forwardController' => false
                    ));
                }

                // Post-processing once installation is complete
                // Need to generate a secret value and merge it into the config
                $secret = hash('sha1', uniqid(mt_rand()));
                $configurator->mergeParameters(array('secret' => $secret));

                $flashes = array();

                // Write the updated config file
                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.installer.error.writing.configuration'
                    );
                }

                // Clear the cache one final time with the updated config
                $this->clearCache();

                return $this->postActionRedirect(array(
                    'viewParameters'  =>  array(
                        'welcome_url' => $this->generateUrl('mautic_core_index'),
                        'parameters'  => $configurator->render(),
                        'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                        'is_writable' => $configurator->isFileWritable(),
                        'version'     => $this->factory->getVersion(),
                        'tmpl'        => $tmpl,
                    ),
                    'returnUrl'         => $this->generateUrl('mautic_installer_final'),
                    'contentTemplate'   => 'MauticInstallBundle:Install:final.html.php',
                    'passthroughVars'   => array(
                        'activeLink'    => '#mautic_installer_index',
                        'mauticContent' => 'installer'
                    ),
                    'flashes'           => $flashes,
                    'forwardController' => false
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'    => $form->createView(),
                'index'   => $index,
                'count'   => $configurator->getStepCount(),
                'version' => $this->factory->getVersion(),
                'tmpl'    => $tmpl,
                'majors'  => $majors,
                'minors'  => $minors,
                'appRoot' => $this->container->getParameter('kernel.root_dir'),
            ),
            'contentTemplate' => $step->getTemplate(),
            'passthroughVars' => array(
                'activeLink'     => '#mautic_installer_index',
                'mauticContent'  => 'installer',
                'route'          => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * Controller action for the final step
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function finalAction()
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $welcomeUrl = $this->generateUrl('mautic_core_index');

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'welcome_url' => $welcomeUrl,
                'parameters'  => $configurator->render(),
                'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                'is_writable' => $configurator->isFileWritable(),
                'version'     => $this->factory->getVersion(),
                'tmpl'        => $tmpl,
            ),
            'contentTemplate' => 'MauticInstallBundle:Install:final.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_installer_index',
                'mauticContent'  => 'installer',
                'route'          => $this->generateUrl('mautic_installer_final'),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * Fetches the message to check if the database does not exist
     *
     * @param string $driver   Database driver
     * @param string $database Database name
     *
     * @return string
     */
    private function checkDatabaseNotExistsMessage($driver, $database)
    {
        switch ($driver) {
            case 'pdo_mysql':
                return "Unknown database '$database'";

            case 'pdo_pgsql':
                return 'database "' . $database . '" does not exist';
        }

        return '';
    }
}
