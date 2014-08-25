<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This file is based on VelvelReportBundle (C) 2012 Velvel IT Solutions
 * and distributed under the GNU Lesser General Public License version 3.
 */

namespace Mautic\ReportBundle\Form;

use Symfony\Component\Validator\Constraints\Collection;

/**
 * Form builder
 *
 * @author r1pp3rj4ck <attila.bukor@gmail.com>
 */
class FormBuilder
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory Form factory
     */
    public function __construct(\Symfony\Component\Form\FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Gets a form
     *
     * @param \Mautic\ReportBundle\Entity\Report $entity  Report Entity
     * @param array                              $options Options
     *
     * @return \Symfony\Component\Form\Form
     */
    public function getForm(\Mautic\ReportBundle\Entity\Report $entity, array $options)
    {
        $form = $this->getFormFactory()->createBuilder('report', $entity, $options);

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
