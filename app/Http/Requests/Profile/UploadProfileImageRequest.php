<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadProfileImageRequest extends FormRequest
{
    public const MAX_IMAGE_KILOBYTES = 4096;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image_type' => ['required', Rule::in(['avatar', 'banner'])],
            'image' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif',
                'max:'.self::MAX_IMAGE_KILOBYTES,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'A imagem excede o limite de 4 MB.',
            'image.mimetypes' => 'Envie uma imagem JPG, PNG, WEBP ou GIF.',
        ];
    }
}
