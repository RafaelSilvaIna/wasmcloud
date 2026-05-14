<?php

namespace Services\Create;

use Models\Create\ProfileTokenModel;

class ProfileTokenService
{
    public function __construct(private ProfileTokenModel $model) {}

    public function issue(int $userId, string $action = 'create', ?int $profileId = null): string
    {
        return $this->model->generateToken($userId, $action, $profileId);
    }

    public function validate(string $token, int $userId): ?array
    {
        return $this->model->validateToken($token, $userId);
    }

    public function consume(string $token): void
    {
        $this->model->consumeToken($token);
    }
}
