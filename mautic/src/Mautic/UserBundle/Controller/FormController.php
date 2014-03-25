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
        $request = $this->get('request');

        $user    = new Entity\User();
        $form    = $this->get('form.factory')->create('user', $user);

        ///Check for a submitted form and process it
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $em = $this->get('doctrine')->getEntityManager();
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
                return $this->redirect($this->generateUrl('mautic_user_index'));
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->ajaxAction($request, array('form' => $form->createView()), "Form:user.html.php");
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
    public function editAction ($userId)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $em->getRepository('MauticUserBundle:User')->find($userId);

        //user not found
        if (empty($user)) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get("translator")->trans(
                    'mautic.user.error.notfound',
                    array("%id%" => $userId),
                    'flashes'
                )
            );
            return $this->redirect($this->generateUrl('mautic_user_index'));
        }

        $form    = $this->get('form.factory')->create('user', $user);
        $request = $this->get('request');

        ///Check for a submitted form and process it
        if ($request->getMethod() == 'POST') {
            //get the original password to save if password is empty from the form
            $originalPassword = ($user->getId()) ? $user->getPassword() : '';

            $form->bind($request);
            if ($form->isValid()) {
                $submittedPassword = $request->request->get('user[password][password]', null, true);
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
                    return $this->redirect($this->generateUrl('mautic_user_index'));
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
                return $this->redirect($this->generateUrl('mautic_user_index'));
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->ajaxAction($request, array('form' => $form->createView()), "Form:user.html.php");
        } else {
            return $this->render('MauticUserBundle:Form:user.html.php',
                array(
                    'form' => $form->createView()
                )
            );
        }
    }
}