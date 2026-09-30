<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint'        => 'required|url|max:500',
            'keys.p256dh'     => 'required|string|max:255',
            'keys.auth'       => 'required|string|max:255',
            'contentEncoding' => 'nullable|string|max:20',
        ]);

        // O endpoint identifica o aparelho/navegador; se outra pessoa logar nele, a inscrição passa a ser dela.
        PushSubscription::updateOrCreate(
            ['endpoint' => $dados['endpoint']],
            [
                'user_id'          => $request->user()->id,
                'public_key'       => $dados['keys']['p256dh'],
                'auth_token'       => $dados['keys']['auth'],
                'content_encoding' => $dados['contentEncoding'] ?? 'aesgcm',
                'user_agent'       => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $dados = $request->validate(['endpoint' => 'required|string|max:500']);

        $request->user()->pushSubscriptions()->where('endpoint', $dados['endpoint'])->delete();

        return response()->json(['ok' => true]);
    }
}
