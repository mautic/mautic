<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserType;
use Symfony\Component\Form\FormFactoryInterface;

final class UserTypeTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * Test that OIDC subject ID field is present when OIDC is enabled and user is not in profile mode.
     */
    public function testOidcSubjectIdFieldVisibleWhenEnabled(): void
    {
        // Enable OIDC for this test
        $this->configParams['open_id_is_enabled'] = 1;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Create form NOT in profile mode (in_profile = false)
        $form = $formFactory->create(UserType::class, $user, ['in_profile' => false]);

        // Assert OIDC field exists
        $this->assertTrue($form->has('subjectID'), 'OIDC subjectID field should be present when OIDC is enabled and not in profile mode');
    }

    /**
     * Test that OIDC subject ID field is NOT present when OIDC is disabled.
     */
    public function testOidcSubjectIdFieldHiddenWhenDisabled(): void
    {
        // Disable OIDC for this test
        $this->configParams['open_id_is_enabled'] = 0;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Create form NOT in profile mode (in_profile = false)
        $form = $formFactory->create(UserType::class, $user, ['in_profile' => false]);

        // Assert OIDC field does NOT exist
        $this->assertFalse($form->has('subjectID'), 'OIDC subjectID field should NOT be present when OIDC is disabled');
    }

    /**
     * Test that OIDC subject ID field is NOT present in profile mode even when OIDC is enabled.
     */
    public function testOidcSubjectIdFieldHiddenInProfileMode(): void
    {
        // Enable OIDC for this test
        $this->configParams['open_id_is_enabled'] = 1;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Create form IN profile mode (in_profile = true)
        $form = $formFactory->create(UserType::class, $user, ['in_profile' => true]);

        // Assert OIDC field does NOT exist in profile mode
        $this->assertFalse($form->has('subjectID'), 'OIDC subjectID field should NOT be present in profile mode');
    }

    /**
     * Test that role and isPublished fields are present when NOT in profile mode.
     */
    public function testRoleAndPublishedFieldsPresentWhenNotInProfile(): void
    {
        $this->configParams['open_id_is_enabled'] = 0;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Create form NOT in profile mode
        $form = $formFactory->create(UserType::class, $user, ['in_profile' => false]);

        // Assert admin fields exist
        $this->assertTrue($form->has('role'), 'Role field should be present when not in profile mode');
        $this->assertTrue($form->has('isPublished'), 'isPublished field should be present when not in profile mode');
    }

    /**
     * Test that role and isPublished fields are NOT present in profile mode.
     */
    public function testRoleAndPublishedFieldsHiddenInProfile(): void
    {
        $this->configParams['open_id_is_enabled'] = 0;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Create form IN profile mode
        $form = $formFactory->create(UserType::class, $user, ['in_profile' => true]);

        // Assert admin fields do NOT exist in profile mode
        $this->assertFalse($form->has('role'), 'Role field should NOT be present in profile mode');
        $this->assertFalse($form->has('isPublished'), 'isPublished field should NOT be present in profile mode');
    }

    /**
     * Test that common fields (username, email, etc.) are always present regardless of mode.
     */
    public function testCommonFieldsAlwaysPresent(): void
    {
        $this->configParams['open_id_is_enabled'] = 0;
        $this->setUpSymfony($this->configParams);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $user = new User();

        // Test in both modes
        foreach ([true, false] as $inProfile) {
            $form = $formFactory->create(UserType::class, $user, ['in_profile' => $inProfile]);

            $mode = $inProfile ? 'profile' : 'admin';

            // Assert common fields exist
            $this->assertTrue($form->has('username'), "Username field should be present in {$mode} mode");
            $this->assertTrue($form->has('firstName'), "First name field should be present in {$mode} mode");
            $this->assertTrue($form->has('lastName'), "Last name field should be present in {$mode} mode");
            $this->assertTrue($form->has('email'), "Email field should be present in {$mode} mode");
            $this->assertTrue($form->has('plainPassword'), "Password field should be present in {$mode} mode");
            $this->assertTrue($form->has('timezone'), "Timezone field should be present in {$mode} mode");
            $this->assertTrue($form->has('locale'), "Locale field should be present in {$mode} mode");
            $this->assertTrue($form->has('buttons'), "Buttons should be present in {$mode} mode");
        }
    }
}
