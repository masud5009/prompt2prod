<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $sessions = ChatSession::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'messages' => fn ($query) => $query->with('images')->orderBy('created_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('ImageGenerator', [
            'initialSessions' => $sessions,
        ]);
    }
}
