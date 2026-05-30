<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_details_with_github_links(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('profile.details.update'), [
            'name' => 'Rafael Silva Inacio',
            'github_url' => 'https://github.com/rafael',
            'github_repository_url' => 'https://github.com/rafael/wasm-cloud',
        ]);

        $response->assertOk()
            ->assertJsonPath('profile.name', 'Rafael Silva Inacio')
            ->assertJsonPath('profile.github_url', 'https://github.com/rafael');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'github_repository_url' => 'https://github.com/rafael/wasm-cloud',
        ]);
    }

    public function test_profile_rejects_non_github_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('profile.details.update'), [
            'name' => 'Rafael Silva Inacio',
            'github_url' => 'https://example.com/rafael',
            'github_repository_url' => 'https://example.com/rafael/repo',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['github_url', 'github_repository_url']);
    }

    public function test_user_can_update_banner_color(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('profile.appearance.update'), [
            'banner_color' => '#1a1a1a',
        ])->assertOk()
            ->assertJsonPath('profile.banner_color', '#1a1a1a');
    }

    public function test_user_can_upload_profile_image_through_imgbb(): void
    {
        Config::set('services.imgbb.key', 'testing-key');
        Http::fake([
            'api.imgbb.com/*' => Http::response([
                'data' => ['display_url' => 'https://i.ibb.co/avatar.jpg'],
            ]),
        ]);

        $user = User::factory()->create();
        $imagePath = tempnam(sys_get_temp_dir(), 'wasm-avatar').'.png';
        file_put_contents($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        $this->actingAs($user)->postJson(route('profile.image.upload'), [
            'image_type' => 'avatar',
            'image' => new UploadedFile($imagePath, 'avatar.png', 'image/png', null, true),
        ])->assertOk()
            ->assertJsonPath('profile.profile_photo_url', 'https://i.ibb.co/avatar.jpg');
    }
}
