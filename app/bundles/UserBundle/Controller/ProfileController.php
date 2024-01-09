<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileController extends FormController
{
    /**
     * Generate's account profile.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, LanguageHelper $languageHelper, UserPasswordHasherInterface $hasher, TokenStorageInterface $tokenStorage)
    {
        // get current user
        $me = $tokenStorage->getToken()->getUser();
        \assert($me instanceof User);
        /** @var UserModel $model */
        $model = $this->getModel('user');

        // set some permissions
        $permissions = [
            'apiAccess' => ($this->coreParametersHelper->get('api_enabled')) ?
                $this->security->isGranted('api:access:full')
                : 0,
            'editName'     => $this->security->isGranted('user:profile:editname'),
            'editUsername' => $this->security->isGranted('user:profile:editusername'),
            'editPosition' => $this->security->isGranted('user:profile:editposition'),
            'editEmail'    => $this->security->isGranted('user:profile:editemail'),
        ];

        $action = $this->generateUrl('mautic_user_account');
        $form   = $model->createForm($me, $this->formFactory, $action, ['in_profile' => true]);

        $overrides = [];

        // make sure this user has access to edit privileged fields
        foreach ($permissions as $permName => $hasAccess) {
            if ('apiAccess' == $permName) {
                continue;
            }

            if (!$hasAccess) {
                // set the value to its original
                switch ($permName) {
                    case 'editName':
                        $overrides['firstName'] = $me->getFirstName();
                        $overrides['lastName']  = $me->getLastName();
                        $form->remove('firstName');
                        $form->add(
                            'firstName_unbound',
                            TextType::class,
                            [
                                'label'      => 'mautic.core.firstname',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => ['class' => 'form-control'],
                                'mapped'     => false,
                                'disabled'   => true,
                                'data'       => $me->getFirstName(),
                                'required'   => false,
                            ]
                        );

                        $form->remove('lastName');
                        $form->add(
                            'lastName_unbound',
                            TextType::class,
                            [
                                'label'      => 'mautic.core.lastname',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => ['class' => 'form-control'],
                                'mapped'     => false,
                                'disabled'   => true,
                                'data'       => $me->getLastName(),
                                'required'   => false,
                            ]
                        );
                        break;

                    case 'editUsername':
                        $overrides['username'] = $me->getUserIdentifier();
                        $form->remove('username');
                        $form->add(
                            'username_unbound',
                            TextType::class,
                            [
                                'label'      => 'mautic.core.username',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => ['class' => 'form-control'],
                                'mapped'     => false,
                                'disabled'   => true,
                                'data'       => $me->getUserIdentifier(),
                                'required'   => false,
                            ]
                        );
                        break;
                    case 'editPosition':
                        $overrides['position'] = $me->getPosition();
                        $form->remove('position');
                        $form->add(
                            'position_unbound',
                            TextType::class,
                            [
                                'label'      => 'mautic.core.position',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => ['class' => 'form-control'],
                                'mapped'     => false,
                                'disabled'   => true,
                                'data'       => $me->getPosition(),
                                'required'   => false,
                            ]
                        );
                        break;
                    case 'editEmail':
                        $overrides['email'] = $me->getEmail();
                        $form->remove('email');
                        $form->add(
                            'email_unbound',
                            TextType::class,
                            [
                                'label'      => 'mautic.core.type.email',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => ['class' => 'form-control'],
                                'mapped'     => false,
                                'disabled'   => true,
                                'data'       => $me->getEmail(),
                                'required'   => false,
                            ]
                        );
                        break;
                }
            }
        }

        // Check for a submitted form and process it
        $submitted = $request->getSession()->get('formProcessed', 0);
        if ('POST' === $request->getMethod() && !$submitted) {
            $request->getSession()->set('formProcessed', 1);

            // check to see if the password needs to be rehashed
            $formUser              = $request->request->get('user') ?? [];
            $submittedPassword     = $formUser['plainPassword']['password'] ?? null;
            $overrides['password'] = $model->checkNewPassword($me, $hasher, $submittedPassword);
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    foreach ($overrides as $k => $v) {
                        $func = 'set'.ucfirst($k);
                        $me->$func($v);
                    }

                    // form is valid so process the data
                    $model->saveEntity($me);

                    // check if the user's locale has been downloaded already, fetch it if not
                    $installedLanguages = $languageHelper->getSupportedLanguages();

                    if ($me->getLocale() && !array_key_exists($me->getLocale(), $installedLanguages)) {
                        $fetchLanguage = $languageHelper->extractLanguagePackage($me->getLocale());

                        // If there is an error, we need to reset the user's locale to the default
                        if ($fetchLanguage['error']) {
                            $me->setLocale(null);
                            $model->saveEntity($me);
                            $message     = 'mautic.core.could.not.set.language';
                            $messageVars = [];

                            if (isset($fetchLanguage['message'])) {
                                $message = $fetchLanguage['message'];
                            }

                            if (isset($fetchLanguage['vars'])) {
                                $messageVars = $fetchLanguage['vars'];
                            }

                            $this->addFlashMessage($message, $messageVars);
                        }
                    }

                    // Update timezone and locale
                    $tz = $me->getTimezone();
                    if (empty($tz)) {
                        $tz = $this->coreParametersHelper->get('default_timezone');
                    }
                    $request->getSession()->set('_timezone', $tz);

                    $locale = $me->getLocale();
                    if (empty($locale)) {
                        $locale = $this->coreParametersHelper->get('locale');
                    }
                    $request->getSession()->set('_locale', $locale);

                    $returnUrl = $this->generateUrl('mautic_user_account');

                    return $this->postActionRedirect(
                        [
                            'returnUrl'       => $returnUrl,
                            'contentTemplate' => 'Mautic\UserBundle\Controller\ProfileController::indexAction',
                            'passthroughVars' => [
                                'mauticContent' => 'user',
                            ],
                            'flashes' => [ // success
                                [
                                    'type' => 'notice',
                                    'msg'  => 'mautic.user.account.notice.updated',
                                ],
                            ],
                        ]
                    );
                }
            } else {
                return $this->redirectToRoute('mautic_dashboard_index');
            }
        }
        $request->getSession()->set('formProcessed', 0);

        $parameters = [
            'permissions'       => $permissions,
            'me'                => $me,
            'userForm'          => $form->createView(),
            'authorizedClients' => $this->forward('Mautic\ApiBundle\Controller\ClientController::authorizedClientsAction')->getContent(),
        ];

        return $this->delegateView(
            [
                'viewParameters'  => $parameters,
                'contentTemplate' => '@MauticUser/Profile/index.html.twig',
                'passthroughVars' => [
                    'route'         => $this->generateUrl('mautic_user_account'),
                    'mauticContent' => 'user',
                ],
            ]
        );
    }
}
