<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 1/14/15
 * Time: 7:42 PM
 */

namespace MauticAddon\MauticSocialBundle\Integration;


use Mautic\AddonBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\FormBuilder;

abstract class SocialIntegration extends AbstractIntegration
{

    /**
     * @param FormBuilder $builder
     */
    public function appendToFeatureForm(FormBuilder &$builder)
    {
        $name = strtolower($this->getName());
        if ($this->factory->serviceExists('mautic.form.type.social.'.$name)) {
            $builder->add('shareButton', 'socialmedia_' . $name, array(
                'label'    => 'mautic.integration.form.sharebutton',
                'required' => false
            ));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $silenceExceptions
     *
     * @return array
     */
    public function getFormLeadFields($silenceExceptions = true)
    {
        static $fields = array();

        if (empty($fields)) {
            $translator = $this->factory->getTranslator();
            $s          = $this->getName();
            $available  = $this->getAvailableFields($silenceExceptions);
            if (empty($available) || !is_array($available)) {
                return array();
            }
            //create social profile fields
            $socialProfileUrls = $this->factory->getHelper('integration')->getSocialProfileUrlRegex();

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                            }
                        } else {
                            $fields[$field] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        }
                        break;
                    case 'array_object':
                        if ($field == "urls" || $field == "url") {
                            foreach ($socialProfileUrls as $p => $d) {
                                $fields["{$p}ProfileHandle"] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$p}ProfileHandle") : $label;
                            }
                            foreach ($details['fields'] as $f) {
                                $fields["{$f}Urls"] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$f}Urls") : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        }
                        break;
                }
            }
            if ($this->sortFieldsAlphabetically()) {
                uasort($fields, "strnatcmp");
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }
}