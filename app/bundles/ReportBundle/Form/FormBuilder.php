<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This file was originally distributed as part of VelvelReportBundle (C) 2012 Velvel IT Solutions
 * and distributed under the GNU Lesser General Public License version 3.
 */

namespace Mautic\ReportBundle\Form;

use Symfony\Component\Validator\Constraints\Collection;

/**
 * Form builder
 *
 * @author r1pp3rj4ck <attila.bukor@gmail.com>
 */
class FormBuilder implements FormBuilderInterface
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory Form factory
     *
     * @author   r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function __construct(\Symfony\Component\Form\FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Gets a form
     *
     * @param array $parameters Parameters
     * @param array $options    Options
     *
     * @return \Symfony\Component\Form\Form
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getForm(array $parameters, array $options)
    {
        $formData        = array();
        $validationArray = array();
        foreach ($parameters as $key => $value) {
            if (isset($value['value'])) {
                $formData[$key] = $value['value'];
            }
        }
        $form = $this->getFormFactory()->createBuilder('report', $formData, $options);

        foreach ($parameters as $key => $value) {
            if (isset($value['options'])) {
                $form->add($key, $value['type'], $value['options']);
            }
            else {
                $form->add($key, $value['type']);
            }
        }

        return $form->getForm();
    }

    /**
     * Gets a FormFactoryInterface object from the site factory
     *
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }
}
