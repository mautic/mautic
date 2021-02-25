<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\ProgressiveProfiling;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\ProgressiveProfiling\DisplayCounter;
use Mautic\FormBundle\ProgressiveProfiling\DisplayManager;

class DisplayManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var array
     */
    private $viewOnlyFields;

    /**
     * @var DisplayCounter
     */
    private $displayCounter;

    public function setUp(): void
    {
        $this->viewOnlyFields = [];
        $this->form           = new Form();
        $this->displayCounter = new DisplayCounter($this->form);
    }

    public function testShowForField()
    {
        $form           = new Form();
        $viewOnlyFields = ['button'];
        $displayManager = new DisplayManager($form, $viewOnlyFields);
        $displayCounter = $displayManager->getDisplayCounter();

        $field = new Field();
        $this->assertTrue($displayManager->showForField($field));

        $field->setType('button');
        $this->assertTrue($displayManager->showForField($field));

        $field->setType('text');

        // display If first field is always display and progressive limit 1
        $field->setAlwaysDisplay(true);
        $form->setProgressiveProfilingLimit(1);
        $this->assertTrue($displayManager->showForField($field));

        // not display If second field is always display and progressive limit 1
        $displayCounter->increaseDisplayedFields();
        $this->assertFalse($displayManager->showForField($field));
    }
}
