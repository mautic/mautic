<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class FormModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class FormModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Form');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'form:forms';
    }


    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(array('Form'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('mauticform', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Form();
        }

        $entity = parent::getEntity($id);


        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(array('Form'));
        }

        switch ($action) {
            case "pre_save":
                $name = FormEvents::FORM_PRE_SAVE;
                break;
            case "post_save":
                $name = FormEvents::FORM_POST_SAVE;
                break;
            case "pre_delete":
                $name = FormEvents::FORM_PRE_DELETE;
                break;
            case "post_delete":
                $name = FormEvents::FORM_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FormEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }

    /**
     * @param Form $entity
     * @param      $sessionFields
     */
    public function setFields(Form &$entity, $sessionFields)
    {
        $order   = 1;
        $aliases = array();
        $existingFields = $entity->getFields();

        foreach ($sessionFields as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingFields[$properties['id']])) ? false : true;
            $field = !$isNew ? $existingFields[$properties['id']] : new Field();

            if (!$isNew) {
                if (empty($properties['alias'])) {
                    $properties['alias'] = $field->getAlias();
                }
                if (empty($properties['label'])) {
                    $properties['label'] = $field->getLabel();
                }
            }

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order')))
                    continue;

                if ($f == 'alias') {
                    $alias = strtolower(InputHelper::alphanum($properties['label']));

                    //make sure alias is not already taken
                    $testAlias = $alias;

                    $count     = (int) in_array($alias, $aliases);
                    $aliasTag  = $count;

                    while ($count) {
                        $testAlias = $alias . $aliasTag;
                        $count     = (int) in_array($testAlias, $aliases);
                        $aliasTag++;
                    }

                    $v = $testAlias;
                    $aliases[] = $v;
                }

                $func = "set" .  ucfirst($f);
                if (method_exists($field, $func)) {
                    $field->$func($v);
                }
                $field->setForm($entity);
            }
            $field->setOrder($order);
            $order++;
            $entity->addField($properties['id'], $field);
        }
    }

    /**
     * @param Form $entity
     * @param      $sessionActions
     */
    public function setActions(Form &$entity, $sessionActions)
    {
        $order   = 1;
        $existingActions = $entity->getActions();

        foreach ($sessionActions as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $action = !$isNew ? $existingActions[$properties['id']] : new Action();

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order')))
                    continue;

                $func = "set" .  ucfirst($f);
                if (method_exists($action, $func)) {
                    $action->$func($v);
                }
                $action->setForm($entity);
            }
            $action->setOrder($order);
            $order++;
            $entity->addAction($properties['id'], $action);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getName(), true));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, true));
        }

        //make sure alias is not already taken
        $testAlias = $alias;
        $count     = $this->em->getRepository('MauticFormBundle:Form')->checkUniqueAlias($testAlias, $entity->getId());
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $count     = $this->em->getRepository('MauticFormBundle:Form')->checkUniqueAlias($testAlias, $entity->getId());
            $aliasTag++;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        //generate cached HTML and JS
        $templating = $this->factory->getTemplating();

        $html = $templating->render('MauticFormBundle:Builder:form.html.php', array(
            'form' => $entity
        ));

        $html = InputHelper::html($html);

        $style  = $templating->render('MauticFormBundle:Builder:style.html.php', array(
            'form' => $entity
        ));

        $script = $templating->render('MauticFormBundle:Builder:script.html.php', array(
            'form' => $entity
        ));

        $html = $style . $html . $script;
        $entity->setCachedHtml($html);

        //replace line breaks with literal symbol and escape quotations
        $search = array(
            "\n",
            '"'
        );
        $replace = array(
            '\n',
            '\"'
        );
        $html = str_replace($search, $replace, $html);
        $js = "document.write(\"".$html."\");";
        $entity->setCachedJs($js);

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Gets array of custom fields and submit actions from bundles subscribed FormEvents::FORM_ON_BUILD
     *
     * @return mixed
     */
    public function getCustomComponents()
    {
        $session = $this->factory->getSession();
        $customComponents = $session->get('mautic.formcomponents.custom');
        if (empty($customComponents)) {
            //build them
            $event = new FormBuilderEvent($this->translator);
            $this->dispatcher->dispatch(FormEvents::FORM_ON_BUILD, $event);
            $customComponents['fields']  = $event->getFormFields();
            $customComponents['actions'] = $event->getSubmitActions();
            $session->set('mautic.formcomponents.custom', $customComponents);
        }
        return $customComponents;
    }
}