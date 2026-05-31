<?php

namespace App\Http\Requests\Workspace;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkspaceRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:600'],
            'accepted_guidelines' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->user()?->workspaces()->count() >= Workspace::LIMIT_PER_USER) {
                    $validator->errors()->add('name', 'Sua conta atingiu o limite de 8 workspaces.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'accepted_guidelines.accepted' => 'Confirme que voce entende e segue as diretrizes da Wasm Cloud.',
        ];
    }
}
