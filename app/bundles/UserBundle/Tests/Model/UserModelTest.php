<?php

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Model;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var MailHelper
     */
    private $mailHelper;

    /**
     * @var UserTokenServiceInterface
     */
    private $userTokenServiceInterface;

    public function setUp()
    {
        $this->mailHelper = $this->createMock(MailHelper::class);
        $this->userTokenServiceInterface = $this->createMock(UserTokenServiceInterface::class);

        $this->userModel = new UserModel($this->mailHelper, $this->userTokenServiceInterface);
    }

    public function
}
