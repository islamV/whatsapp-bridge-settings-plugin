<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappBridgeInstanceRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class WhatsappBridgeInstanceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [WhatsappBridgeSettingsPluginServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('whatsapp_bridge_instances', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('api_base_url');
            $table->text('api_token')->nullable();
            $table->string('session_name');
            $table->string('sender')->nullable();
            $table->unsignedInteger('timeout')->default(30);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function test_it_saves_encrypted_instances_and_resolves_default(): void
    {
        $repository = new WhatsappBridgeInstanceRepository;

        $instance = $repository->save([
            'name' => 'Primary',
            'api_base_url' => 'https://bridge.test',
            'api_token' => 'secret-token',
            'session_name' => 'primary',
            'is_default' => true,
        ]);

        $this->assertSame('Primary', $instance['name']);
        $this->assertSame('secret-token', $repository->default()['api_token']);
        $this->assertNotSame('secret-token', (string) \DB::table('whatsapp_bridge_instances')->value('api_token'));
        $this->assertSame('secret-token', Crypt::decryptString((string) \DB::table('whatsapp_bridge_instances')->value('api_token')));
    }

    public function test_it_supports_multiple_instances_and_default_switching(): void
    {
        $repository = new WhatsappBridgeInstanceRepository;
        $first = $repository->save(['name' => 'One', 'api_base_url' => 'https://one.test', 'api_token' => 'one', 'session_name' => 'one', 'is_default' => true]);
        $second = $repository->save(['name' => 'Two', 'api_base_url' => 'https://two.test', 'api_token' => 'two', 'session_name' => 'two']);

        $repository->save(['name' => 'Two', 'api_base_url' => 'https://two.test', 'api_token' => 'two', 'session_name' => 'two', 'is_default' => true], $second['id']);

        $this->assertSame($second['id'], $repository->default()['id']);
        $this->assertCount(2, $repository->all());
        $this->assertTrue($repository->delete($first['id']));
    }
}
