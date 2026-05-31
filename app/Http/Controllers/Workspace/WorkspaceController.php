<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function dashboard(Request $request)
    {
        return view('pages.dashboard.index', [
            'workspaces' => $request->user()
                ->workspaces()
                ->latest()
                ->get(),
            'workspaceLimit' => Workspace::LIMIT_PER_USER,
        ]);
    }

    public function create()
    {
        return view('pages.workspaces.create', [
            'workspaceLimit' => Workspace::LIMIT_PER_USER,
        ]);
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $workspace = DB::transaction(function () use ($user, $validated): Workspace {
            $baseSlug = Str::slug($validated['name']) ?: 'workspace';
            $slug = $this->uniqueSlug($user->id, $baseSlug);

            return Workspace::create([
                'owner_user_id' => $user->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'plan_model' => 'micro',
                'guidelines_accepted_at' => now(),
                'security_manifest' => [
                    'workspace_scope' => 'owner_isolated',
                    'default_plan' => 'micro',
                    'team_permissions' => 'least_privilege_ready',
                    'billing_scope' => 'workspace_level',
                    'audit_baseline' => 'prepared',
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        });

        return response()->json([
            'message' => 'Workspace criado com seguranca.',
            'workspace' => $this->payload($workspace),
            'redirect' => route('dashboard'),
        ], 201);
    }

    private function uniqueSlug(int $userId, string $baseSlug): string
    {
        $slug = $baseSlug;
        $index = 2;

        while (Workspace::query()->where('owner_user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'description' => $workspace->description,
            'plan_model' => $workspace->plan_model,
            'created_at' => $workspace->created_at?->toIso8601String(),
        ];
    }
}
