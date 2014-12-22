<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/22/14
 * Time: 4:39 AM
 */

namespace Mautic\UserBundle\Controller;


use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormError;

class PublicController extends FormController
{
    /**
     * Generates a new password for the user and emails it to them
     */
    public function passwordResetAction ()
    {
        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->factory->getModel('user');

        $data   = array('identifier' => '');
        $action = $this->generateUrl('mautic_user_passwordreset');
        $form   = $this->get('form.factory')->create('passwordreset', $data, array('action' => $action));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if ($isValid = $this->isFormValid($form)) {
                //find the user
                $data = $form->getData();
                $user = $model->getRepository()->findByIdentifier($data['identifier']);

                if ($user == null) {
                    $form['identifier']->addError(new FormError($this->factory->getTranslator()->trans('mautic.user.user.passwordreset.nouserfound', array(), 'validators')));
                } else {
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $model->resetPassword($user, $encoder);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.user.user.notice.passwordreset', array(), 'flashes')
                    );

                    return $this->redirect($this->generateUrl('login'));
                }
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form' => $form->createView()
            ),
            'contentTemplate' => 'MauticUserBundle:Security:reset.html.php',
            'passthroughVars' => array(
                'route' => $action
            )
        ));
    }
}