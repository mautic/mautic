<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Unit\Api;

use Mautic\NotificationBundle\Api\OneSignalApi;
use PHPUnit\Framework\TestCase;

class OneSignalApiTest extends TestCase
{
    public function testAddMobileData(): void
    {
        $mockOneSignalApi = $this->createMock(OneSignalApi::class);

        $controllerReflection = (new \ReflectionClass(OneSignalApi::class));
        $method               = $controllerReflection->getMethod('addMobileData');
        $method->setAccessible(true);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_subtitle' => 'test']]);
        $this->assertEquals(['subtitle' => ['en' => 'test']], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_sound' => 'test']]);
        $this->assertEquals(['ios_sound' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_sound' => '']]);
        $this->assertEquals(['ios_sound' => 'default'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_badges' => 'test']]);
        $this->assertEquals(['ios_badgeType' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_badgeCount' => '5']]);
        $this->assertEquals(['ios_badgeCount' => 5], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_contentAvailable' => true]]);
        $this->assertEquals(['content_available' => true], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['ios_mutableContent' => true]]);
        $this->assertEquals(['mutable_content' => true], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_sound' => 'test']]);
        $this->assertEquals(['android_sound' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_small_icon' => 'test']]);
        $this->assertEquals(['small_icon' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_large_icon' => 'test']]);
        $this->assertEquals(['large_icon' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_big_picture' => 'test']]);
        $this->assertEquals(['big_picture' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_led_color' => 'test']]);
        $this->assertEquals(['android_led_color' => 'FFTEST'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_accent_color' => 'test']]);
        $this->assertEquals(['android_accent_color' => 'FFTEST'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_group_key' => 'test']]);
        $this->assertEquals(['android_group' => 'test'], $data);

        $data = [];
        $method->invokeArgs($mockOneSignalApi, [&$data, ['android_lockscreen_visibility' => 1]]);
        $this->assertEquals(['android_visibility' => 1], $data);

        $data         = [];
        $mobileConfig = ['additional_data' => ['list' => [
            ['label' => 'a', 'value' => 1],
            ['label' => 'b', 'value' => 2],
        ],
        ],
        ];
        $method->invokeArgs($mockOneSignalApi, [&$data, $mobileConfig]);
        $this->assertEquals(['data' => ['a' => 1, 'b' => 2]], $data);
    }
}
