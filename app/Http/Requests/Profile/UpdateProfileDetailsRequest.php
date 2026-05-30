<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProfileDetailsRequest extends FormRequest
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
            'github_url' => ['nullable', 'string', 'max:255', 'url:https'],
            'github_repository_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $githubUrl = (string) $this->input('github_url', '');
                $repositoryUrl = (string) $this->input('github_repository_url', '');

                if ($githubUrl !== '' && ! $this->isGithubProfileUrl($githubUrl)) {
                    $validator->errors()->add('github_url', 'Informe uma URL valida de perfil do GitHub, como https://github.com/usuario.');
                }

                if ($repositoryUrl !== '' && ! $this->isGithubRepositoryUrl($repositoryUrl)) {
                    $validator->errors()->add('github_repository_url', 'Informe uma URL valida de repositorio GitHub, como https://github.com/usuario/repositorio.');
                }
            },
        ];
    }

    private function isGithubProfileUrl(string $url): bool
    {
        return preg_match('~^https://github\.com/[A-Za-z0-9](?:[A-Za-z0-9-]{0,38}[A-Za-z0-9])?/?$~', $url) === 1;
    }

    private function isGithubRepositoryUrl(string $url): bool
    {
        $owner = '[A-Za-z0-9](?:[A-Za-z0-9-]{0,38}[A-Za-z0-9])?';
        $repo = '[A-Za-z0-9._-]{1,100}';

        return preg_match("~^https://github\\.com/{$owner}/{$repo}(?:\\.git)?/?$~", $url) === 1
            || preg_match("~^git@github\\.com:{$owner}/{$repo}\\.git$~", $url) === 1;
    }
}
