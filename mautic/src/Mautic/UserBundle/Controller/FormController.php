<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



namespace Mautic\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\Userbundle\Entity as Entity;
use Mautic\UserBundle\Form\Type as FormType;

/**
 * Class DefaultController
 *
 * @package Mautic\UserBundle\Controller
 */
class FormController extends CommonController
{

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $request    = $this->get('request');
        //retrieve the user entity
        $user       = new Entity\User();
        //set action URL
        $action  = $this->generateUrl('mautic_user_action', array("objectAction" => "new"));
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_user_index');
        //set the page we came from
        $page    = $this->get('session')->get("mautic.user.page", 1);

        $form       = $this->get('form.factory')->create('user', $user, array('action' => $action));

        ///Check for a submitted form and process it
        if ($request->getMethod() == 'POST') {
            //bind request to the form
            $form->bind($request);

            //redirect if the cancel button was clicked
            if ($form->get('cancel')->isClicked()) {
                return $this->postAction($request,
                    $returnUrl,
                    array("page" => $page),
                    "Default:index",
                    array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl
                    ),
                    true
                );
            }

            //validate the data
            if ($form->isValid()) {
                $em = $this->get('doctrine')->getEntityManager();
                //set the date/time for new submission
                $user->setDateAdded(new \DateTime());
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add(
                    'notice',
                    $this->get("translator")->trans(
                        'mautic.user.notice.created',
                        array("%name%" => $user->getFullName()),
                        'flashes'
                    )
                );

                return $this->postAction($request,
                    $returnUrl,
                    array("page" => $page),
                    "Default:index",
                    array(
                        'activeLink'    => '#mautic_user_index',
                        'route'         => $returnUrl
                    ),
                    true
                );
            }
        }

        if ($request->isXmlHttpRequest() && !$request->get("ignoreAjax", false)) {
            return $this->ajaxAction(
                $request,
                array('form' => $form->createView()),
                "Form:user.html.php",
                array(
                    'ajaxForms'  => array('user'),
                    'activeLink' => '#mautic_user_new',
                    'route'      => $action
                )
            );
        } else {
            return $this->render('MauticUserBundle:Form:user.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        $em      = $this->getDoctrine()->getEntityManager();
        $user    = $em->getRepository('MauticUserBundle:User')->find($objectId);
        $request = $this->get('request');
        //set the page we came from
        $page    = $this->get('session')->get("mautic.user.page", 1);
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_user_index', array("page" => $page));

        //user not found
        if (empty($user)) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get("translator")->trans(
                    'mautic.user.error.notfound',
                    array("%id%" => $objectId),
                    'flashes'
                )
            );
            return $this->postAction($request,
                $returnUrl,
                array("page" => $page),
                "Default:index",
                array(
                    'activeLink'    => '#mautic_user_index',
                    'route'         => $returnUrl
                ),
                true
            );
        }

        //set action URL
        $action     = $this->generateUrl('mautic_user_action',
            array("objectAction" => "edit", "objectId" => $objectId)
        );

        $form       = $this->get('form.factory')->create('user', $user, array('action' => $action));

        ///Check for a submitted form and process it
        if ($request->getMethod() == 'POST') {
            //get the original password to save if password is empty from the form
            $originalPassword = ($user->getId()) ? $user->getPassword() : '';

            //bind the request to the form
            $form->bind($request);

            //redirect before saving if the cancel button was clicked
            if ($form->get('cancel')->isClicked()) {
                return $this->postAction($request,
                        $returnUrl,
                        array("page" => $page),
                        "Default:index",
                        array(
                            'activeLink'    => '#mautic_user_index',
                            'route'         => $returnUrl
                        ),
                        true
                    );
            } else {
                //validate the form
                if ($form->isValid()) {
                    //check to see if the password needs to be rehashed
                    $submittedPassword = $request->request->get('user[password][password]', null, true);
                    //note if the data be persisted to the db
                    $persist = true;
                    if (!empty($submittedPassword)) {
                        //hash the clear password submitted via the form
                        $security = $this->get('security.encoder_factory');
                        $encoder  = $security->getEncoder($user);
                        $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                        $user->setPassword($password);
                    } elseif ($user->getId() && !empty($originalPassword)) {
                        //This is an existing user with a blank password so set the original password
                        $user->setPassword($originalPassword);
                    } else {
                        //should never see this but just in case
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get("translator")->trans(
                                'mautic.security.accessdenied',
                                'flashes'
                            )
                        );
                        return $this->postAction($request,
                            $returnUrl,
                            array("page" => $page),
                            "Default:index",
                            array(
                                'activeLink'    => '#mautic_user_index',
                                'route'         => $returnUrl
                            ),
                            true
                        );
                    }

                    $em = $this->get('doctrine')->getEntityManager();
                    $em->persist($user);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add(
                        'notice',
                        $this->get("translator")->trans(
                            'mautic.user.notice.updated',
                            array("%name%" => $user->getFullName()),
                            'flashes'
                        )
                    );
                    return $this->postAction($request,
                        $returnUrl,
                        array("page" => $page),
                        "Default:index",
                        array(
                            'activeLink'    => '#mautic_user_index',
                            'route'         => $returnUrl
                        ),
                        true
                    );
                }
            }
        }

        if ($request->isXmlHttpRequest() && !$request->get("ignoreAjax", false)) {
            return $this->ajaxAction(
                $request,
                array('form' => $form->createView()),
                "Form:user.html.php",
                array(
                    'ajaxForms'   => array('user'),
                    'activeLink'  => '#mautic_user_index',
                    'route'       => $action
                )
            );
        } else {
            return $this->render('MauticUserBundle:Form:user.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    public function deleteAction($objectId, Request $request) {
        $currentUser = $this->get('security.context')->getToken()->getUser();
        $page        = $this->get('session')->get("mautic.user.page", 1);
        $returnUrl   = $this->generateUrl('mautic_user_index', array("page" => $page));
        $success     = 0;

        if ($request->getMethod() == 'POST') {
            //ensure the user logged in is not getting deleted
            if ((int) $currentUser->getId() !== (int) $objectId) {
                $em   = $this->get('doctrine')->getEntityManager();
                $repo = $em->getRepository('MauticUserBundle:User');
                $user = $repo->find($objectId);

                if (empty($user)) {
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get("translator")->trans(
                            'mautic.user.error.notfound',
                            array("%id%" => $objectId),
                            'flashes'
                        )
                    );
                } else {
                    $success = 1;

                    //get placeholders for flash
                    $name    = $user->getFullName();

                    //delete user
                    $em->remove($user);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'notice',
                        $this->get("translator")->trans(
                            'mautic.user.notice.deleted',
                            array(
                                "%name%" => $name,
                                "%id%"   => $objectId
                            ),
                            'flashes'
                        )
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get("translator")->trans(
                        'mautic.user.error.cannotdeleteself',
                        array(),
                        'flashes'
                    )
                );
            }
        } //else don't do anything

        return $this->postAction($request,
            $returnUrl,
            array("page" => $page),
            "Default:index",
            array(
                'activeLink'    => '#mautic_user_index',
                'route'         => $returnUrl,
                'success'       => $success
            ),
            true
        );
    }
}