<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 *
 * @package MauticAddon\MauticChatBundle\Form\Type
 */
class ConfigType extends AbstractType
{

    private $mediaDir;

    public function __construct(MauticFactory $factory)
    {
        $this->mediaDir = $factory->getSystemPath('assets', true) . '/sounds';
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $finder = new Finder();
        $files  = $finder->files()->in($this->mediaDir)->ignoreDotFiles(true);

        $sounds = array();
        foreach ($files as $file) {
            $sound = $file->getBasename('.' .$file->getExtension());
            if (!isset($sounds[$sound])) {
                $sounds[$sound] = $sound;
            }
        }
        asort($sounds);

        $data = (empty($options['data']['chat_notification_sound'])) ? 'wet' : $options['data']['chat_notification_sound'];
        $builder->add('chat_notification_sound', 'choice', array(
            'choices'  => $sounds,
            'label'    => 'mautic.chat.chat.form.notification_sound',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.chat.chat.form.notification_sound.tooltip'
            ),
            'empty_value' => false,
            'data'        => $data
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chatconfig';
    }
}