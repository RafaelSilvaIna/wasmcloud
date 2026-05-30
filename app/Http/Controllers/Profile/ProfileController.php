<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileAppearanceRequest;
use App\Http\Requests\Profile\UpdateProfileDetailsRequest;
use App\Http\Requests\Profile\UploadProfileImageRequest;
use App\Services\ImgbbImageUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('pages.profile.show', [
            'profileUser' => $request->user(),
        ]);
    }

    public function updateDetails(UpdateProfileDetailsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->forceFill([
            'name' => $validated['name'],
            'github_url' => $validated['github_url'] ?: null,
            'github_repository_url' => $validated['github_repository_url'] ?: null,
        ])->save();

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'profile' => $this->profilePayload($user->refresh()),
        ]);
    }

    public function updateAppearance(UpdateProfileAppearanceRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'banner_color' => $request->validated('banner_color'),
        ])->save();

        return response()->json([
            'message' => 'Aparencia atualizada com sucesso.',
            'profile' => $this->profilePayload($user->refresh()),
        ]);
    }

    public function uploadImage(UploadProfileImageRequest $request, ImgbbImageUploader $uploader): JsonResponse
    {
        $user = $request->user();
        $imageType = $request->validated('image_type');
        $column = $imageType === 'avatar' ? 'profile_photo_url' : 'banner_image_url';

        try {
            $url = $uploader->upload($request->file('image'), "user-{$user->id}-{$imageType}");
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $user->forceFill([$column => $url])->save();

        return response()->json([
            'message' => $imageType === 'avatar' ? 'Foto de perfil atualizada.' : 'Banner atualizado.',
            'profile' => $this->profilePayload($user->refresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload($user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo_url' => $user->profile_photo_url,
            'banner_image_url' => $user->banner_image_url,
            'banner_color' => $user->banner_color ?: '#101010',
            'github_url' => $user->github_url,
            'github_repository_url' => $user->github_repository_url,
        ];
    }
}
