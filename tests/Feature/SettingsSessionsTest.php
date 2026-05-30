<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_connected_devices(): void
    {
        Http::fake([
            'ipwho.is/*' => Http::response([
                'success' => true,
                'region' => 'Ceara',
                'city' => 'Cascavel',
                'country' => 'Brasil',
            ]),
        ]);

        $user = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'session-one',
                'user_id' => $user->id,
                'ip_address' => '8.8.8.8',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('settings.sessions.index'));

        $response->assertOk()
            ->assertJsonPath('sessions.0.location', 'Ceara - Cascavel - Brasil')
            ->assertJsonPath('sessions.0.device_type', 'desktop');
    }

    public function test_user_can_destroy_a_specific_session(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'session-to-remove',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('settings.sessions.destroy', 'session-to-remove'))
            ->assertOk()
            ->assertJsonPath('message', 'Dispositivo desconectado.');

        $this->assertDatabaseMissing('sessions', ['id' => 'session-to-remove']);
    }

    public function test_user_can_destroy_other_sessions(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'current-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'payload' => 'payload',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'other-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'payload' => 'payload',
                'last_activity' => now()->timestamp - 10,
            ],
        ]);

        $this->withSession(['_token' => 'test'])
            ->actingAs($user)
            ->deleteJson(route('settings.sessions.destroy-others'))
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
    }
}
