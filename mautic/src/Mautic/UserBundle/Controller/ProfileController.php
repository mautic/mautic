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
use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Entity as Entity;
use Mautic\UserBundle\Form\Type as FormType;

/**
 * Class ProfileController
 *
 * @package Mautic\UserBundle\Controller
 */
class ProfileController extends FormController
{
    /**
     * Generate's account page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //get current user
        $me    = $this->get('security.context')->getToken()->getUser();
        $model = $this->container->get('mautic.model.user');
        //set some permissions
        $permissions = array(
            'apiAccess'    => ($this->container->getParameter('mautic.api_enabled')) ?
                $this->get('mautic.security')->isGranted('api:access:full')
                : 0,
            'editName'     => $this->get('mautic.security')->isGranted('user:profile:editname'),
            'editUsername' => $this->get('mautic.security')->isGranted('user:profile:editusername'),
            'editPosition' => $this->get('mautic.security')->isGranted('user:profile:editposition'),
            'editEmail'    => $this->get('mautic.security')->isGranted('user:profile:editemail')
        );

        $action = $this->generateUrl('mautic_user_account');
        $form   = $model->createForm($me, $action);

        //remove items that cannot be edited by person themselves
        $form->remove('role');
        $form->remove('save');
        $form->remove('cancel');


        //make sure this user has access to edit privileged fields
        foreach ($permissions as $permName => $hasAccess) {
            if ($permName == "apiAccess") continue;

            if (!$hasAccess) {
                //set the value to its original
                switch ($permName) {
                    case "editName":
                        $overrides['firstName'] = $me->getFirstName();
                        $overrides['lastName'] = $me->getLastName();
                        $form->remove("firstName");
                        $form->add('firstName_unbound', 'text', array(
                            'label'      => 'mautic.user.user.form.firstname',
                            'label_attr' => array('class' => 'control-label'),
                            'attr'       => array('class' => 'form-control'),
                            'mapped'     => false,
                            'disabled'   => true,
                            'data'       => $me->getFirstName(),
                            'required'   => false
                        ));

                        $form->remove("lastName");
                        $form->add('lastName_unbound', 'text', array(
                            'label'      => 'mautic.user.user.form.lastname',
                            'label_attr' => array('class' => 'control-label'),
                            'attr'       => array('class' => 'form-control'),
                            'mapped'     => false,
                            'disabled'   => true,
                            'data'       => $me->getLastName(),
                            'required'   => false
                        ));
                        break;

                    case "editUsername":
                        $overrides['username'] = $me->getUsername();
                        $form->remove("username");
                        $form->add('username_unbound', 'text', array(
                            'label'      => 'mautic.user.user.form.username',
                            'label_attr' => array('class' => 'control-label'),
                            'attr'       => array('class' => 'form-control'),
                            'mapped'     => false,
                            'disabled'   => true,
                            'data'       => $me->getUsername(),
                            'required'   => false
                        ));
                        break;
                    case "editPosition":
                        $overrides['position'] = $me->getPosition();
                        $form->remove("position");
                        $form->add('position_unbound', 'text', array(
                            'label'      => 'mautic.user.user.form.position',
                            'label_attr' => array('class' => 'control-label'),
                            'attr'       => array('class' => 'form-control'),
                            'mapped'     => false,
                            'disabled'   => true,
                            'data'       => $me->getPosition(),
                            'required'   => false
                        ));
                        break;
                    case "editEmail":
                        $overrides['email'] = $me->getEmail();
                        $form->remove("email");
                        $form->add('email_unbound', 'text', array(
                            'label'      => 'mautic.user.user.form.email',
                            'label_attr' => array('class' => 'control-label'),
                            'attr'       => array('class' => 'form-control'),
                            'mapped'     => false,
                            'disabled'   => true,
                            'data'       => $me->getEmail(),
                            'required'   => false
                        ));
                        break;

                }
            }
        }

        //add an input to request the current password in order to change existing details
        $form->add('currentPassword', 'password', array(
            'label'       => 'mautic.user.account.form.password.current',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'       => 'form-control',
                'tooltip'     => 'mautic.user.account.form.help.password.current',
                'preaddon'    => 'fa fa-lock'
            )
        ));

        ///Check for a submitted form and process it
        $submitted = $this->get('session')->get('formProcessed', 0);
        if ($this->request->getMethod() == "POST" && !$submitted) {
            $this->get('session')->set('formProcessed', 1);
            $overrides = array();
            //check to see if the password needs to be rehashed
            $submittedPassword = $this->request->request->get('user[plainPassword][password]', null, true);
            $overrides['password'] = $model->checkNewPassword($me, $submittedPassword);

            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                //form is valid so process the data
                $model->saveEntity($me, $overrides);
            }

            if (!empty($valid)) { //cancelled or success
                $returnUrl = $this->generateUrl('mautic_user_account');
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticUserBundle:Profile:index',
                    'passthroughVars' => array(
                        'route'         => $returnUrl,
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array( //success
                            array(
                                'type' => 'notice',
                                'msg'  => 'mautic.user.account.notice.updated'
                            )
                        ) : array()
                ));
            }
        }
        $this->get('session')->set('formProcessed', 0);

        $parameters = array(
            'permissions' => $permissions,
            'me'          => $me,
            'userForm'    => $form->createView()
        );

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction(array(
                'viewParameters'  => $parameters,
                'contentTemplate' => 'MauticUserBundle:Profile:index.html.php',
                'passthroughVars' => array(
                    'route'     => $this->generateUrl('mautic_user_account'),
                    'ajaxForms' => array('user')
                )
            ));
        } else {
            return $this->render('MauticUserBundle:Profile:index.html.php', $parameters);
        }
    }
}