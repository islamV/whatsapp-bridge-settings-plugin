<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappBridgeInstanceRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class WhatsappBridgeTypedMessageTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [WhatsappBridgeSettingsPluginServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    }

    protected function setUp(): void
    {
        parent::setUp();
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
        app(WhatsappBridgeInstanceRepository::class)->save(['name' => 'Primary', 'api_base_url' => 'https://bridge.test', 'api_token' => 'secret', 'session_name' => 'primary', 'is_default' => true]);
    }

    public function test_it_sends_buttons_using_the_typed_endpoint(): void
    {
        Http::fake(['bridge.test/*' => Http::response(['success' => true], 200)]);

        $bridge = app(WhatsappBridge::class);
        $this->assertTrue($bridge->sendButtons('201000000000', 'Choose', [['id' => 'yes', 'title' => 'Yes']]));

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/secret/send/primary')
            && $request->data()['type'] === 'buttons'
            && $request->data()['buttons'][0]['id'] === 'yes');
    }

    public function test_it_sends_media_using_the_typed_endpoint(): void
    {
        Http::fake(['bridge.test/*' => Http::response(['success' => true], 200)]);

        $bridge = app(WhatsappBridge::class);
        $this->assertTrue($bridge->sendMedia('201000000000', 'https://example.test/a.jpg', 'Caption'));

        Http::assertSent(fn ($request) => $request->data()['type'] === 'media'
            && $request->data()['media']['url'] === 'https://example.test/a.jpg');
    }
}
