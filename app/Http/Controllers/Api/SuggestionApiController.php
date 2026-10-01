<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
            'uid' => ['nullable', 'string', 'max:128'],
            'is_premium' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'in:idea,bug,scores_request,usability,other'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'device_info' => ['nullable', 'string', 'max:150'],
        ]);

        $suggestion = Suggestion::create([
            'source' => Suggestion::SOURCE_APP,
            'uid' => $validated['uid'] ?? null,
            'email' => strtolower(trim($validated['email'])),
            'name' => isset($validated['name']) && $validated['name'] !== null ? trim($validated['name']) : null,
            'is_premium' => (bool) ($validated['is_premium'] ?? false),
            'type' => $validated['type'] ?? Suggestion::TYPE_IDEA,
            'subject' => isset($validated['subject']) && $validated['subject'] !== null ? trim($validated['subject']) : null,
            'message' => trim($validated['message']),
            'app_version' => isset($validated['app_version']) && $validated['app_version'] !== null ? trim($validated['app_version']) : null,
            'device_info' => isset($validated['device_info']) && $validated['device_info'] !== null ? trim($validated['device_info']) : null,
            'status' => Suggestion::STATUS_NEW,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sugerencia recibida correctamente por el equipo de ScoreBox.',
            'id' => $suggestion->id,
        ], 201);
    }
}
