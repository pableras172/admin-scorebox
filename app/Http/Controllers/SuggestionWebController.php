<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuggestionWebController extends Controller
{
    public function create(): View
    {
        return view('suggestions.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:idea,bug,scores_request,usability,other'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        Suggestion::create([
            'source' => Suggestion::SOURCE_WEB,
            'email' => strtolower(trim($validated['email'])),
            'name' => $validated['name'] ? trim($validated['name']) : null,
            'type' => $validated['type'],
            'subject' => $validated['subject'] ? trim($validated['subject']) : null,
            'message' => trim($validated['message']),
            'status' => Suggestion::STATUS_NEW,
        ]);

        return redirect()->route('suggestions.create')
            ->with('success', '¡Muchas gracias! Tu sugerencia ha sido enviada al equipo de ScoreBox con éxito.');
    }
}
