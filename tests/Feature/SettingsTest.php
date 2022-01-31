<?php

namespace EscolaLms\Video\Tests\Feature;

use EscolaLms\Auth\Database\Seeders\AuthPermissionSeeder;
use EscolaLms\Core\Tests\CreatesUsers;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use EscolaLms\Video\Providers\SettingsServiceProvider;
use EscolaLms\Video\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class SettingsTest extends TestCase
{
    use CreatesUsers, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\EscolaLms\Settings\EscolaLmsSettingsServiceProvider::class)) {
            $this->markTestSkipped('Settings package not installed');
        }

        $this->seed(AuthPermissionSeeder::class);
        Config::set('escola_settings.use_database', true);
        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('admin');
    }

    protected function tearDown(): void
    {
        \EscolaLms\Settings\Models\Config::truncate();
    }

    public function testAdministrableConfigApi(): void
    {
        $configKey = SettingsServiceProvider::CONFIG_KEY;

        $this->response = $this->actingAs($this->user, 'api')->json(
            'POST',
            '/api/admin/config',
            [
                'config' => [
                    [
                        'key' => "{$configKey}.bitrates",
                        'value' => [
                            [
                                "kiloBitrate" => 500,
                                "scale" => "500:500"
                            ],
                            [
                                "kiloBitrate" => 500,
                                "scale" => "1280:720"
                            ]
                        ],
                    ],
                ]
            ]
        );
        $this->response->assertOk();

        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/admin/config'
        );
        $this->response->assertOk();

        $this->response->assertJsonFragment([
            $configKey => [
                'bitrates' => [
                    'full_key' => "$configKey.bitrates",
                    'key' => 'bitrates',
                    'public' => true,
                    'rules' => [
                        'array'
                    ],
                    'value' => [
                        [
                            'kiloBitrate' => 500,
                            'scale' => '500:500'
                        ],
                        [
                            'kiloBitrate' => 500,
                            'scale' => '1280:720'
                        ]
                    ],
                    'readonly' => false,
                ]
            ],
        ]);
    }
}
