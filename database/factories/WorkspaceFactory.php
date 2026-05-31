<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => $this->faker->sentence(10),
            'plan_model' => 'micro',
            'guidelines_accepted_at' => now(),
            'security_manifest' => [
                'status' => 'prepared',
                'created_by' => 'factory',
            ],
        ];
    }
}
