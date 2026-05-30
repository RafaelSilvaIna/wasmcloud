<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Sessions\SessionDeviceInspector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountSessionController extends Controller
{
    public function index()
    {
        return view('pages.settings.index');
    }

    public function list(Request $request, SessionDeviceInspector $inspector): JsonResponse
    {
        return response()->json([
            'sessions' => $this->sessionsFor($request, $inspector),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function destroy(Request $request, string $session, SessionDeviceInspector $inspector): JsonResponse
    {
        $currentSessionId = $request->session()->getId();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', $session)
            ->delete();

        if (hash_equals($currentSessionId, $session)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Sessao atual encerrada.',
                'redirect' => route('login'),
            ]);
        }

        return response()->json([
            'message' => $deleted ? 'Dispositivo desconectado.' : 'Sessao nao encontrada.',
            'sessions' => $this->sessionsFor($request, $inspector),
        ]);
    }

    public function destroyOthers(Request $request, SessionDeviceInspector $inspector): JsonResponse
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return response()->json([
            'message' => 'Todos os outros dispositivos foram desconectados.',
            'sessions' => $this->sessionsFor($request, $inspector),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => Auth::check(),
            'login_url' => route('login'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sessionsFor(Request $request, SessionDeviceInspector $inspector): array
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => $inspector->inspect((array) $session, $request->session()->getId()))
            ->values()
            ->all();
    }
}
