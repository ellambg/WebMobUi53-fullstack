<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    public function index(Request $request)
    {
        $polls = $request->user()->polls()
            ->withCount('votes')
            ->orderBy('created_at', 'desc')
            ->get();
        return $polls;
    }

    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        $hasVoted = $user ? $poll->votes()->where('user_id', $user->id)->exists() : false;

        return response()->json(array_merge($poll->toArray(), ['has_voted' => $hasVoted]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'is_draft' => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change' => 'boolean',
            'results_public' => 'boolean',
            'duration' => 'nullable|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*.label' => 'required|string|max:255',
        ]);

        $poll = $request->user()->polls()->create([
            'question' => $validated['question'],
            'title' => $validated['title'] ?? null,
            'is_draft' => $validated['is_draft'] ?? true,
            'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
            'allow_vote_change' => $validated['allow_vote_change'] ?? false,
            'results_public' => $validated['results_public'] ?? false,
            'duration' => $validated['duration'] ?? null,
            'secret_token' => Str::random(32),
            'started_at' => isset($validated['is_draft']) && !$validated['is_draft'] ? now() : null,
            'ends_at' => isset($validated['duration']) && isset($validated['is_draft']) && !$validated['is_draft']
                ? now()->addSeconds($validated['duration'])
                : null,
        ]);

        foreach ($validated['options'] as $option) {
            $poll->options()->create(['label' => $option['label']]);
        }

        return response()->json($poll->load('options'), 201);
    }

    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $validated = $request->validate([
            'question' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
            'is_draft' => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change' => 'boolean',
            'results_public' => 'boolean',
            'duration' => 'nullable|integer|min:1',
            'options' => 'sometimes|array|min:2',
            'options.*.label' => 'required_with:options|string|max:255',
        ]);

        // Si on lance le sondage
        if (isset($validated['is_draft']) && !$validated['is_draft'] && $poll->is_draft) {
            $validated['started_at'] = now();
            if (!empty($validated['duration'])) {
                $validated['ends_at'] = now()->addSeconds($validated['duration']);
            }
        }

        $poll->update($validated);

        if (isset($validated['options'])) {
            $poll->options()->delete();
            foreach ($validated['options'] as $option) {
                $poll->options()->create(['label' => $option['label']]);
            }
        }

        return response()->json($poll->load('options'));
    }

    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if ($poll->is_draft) {
            return response()->json(['message' => 'Poll not started.'], 403);
        }

        if ($poll->ends_at && now()->gt($poll->ends_at)) {
            return response()->json(['message' => 'Poll has ended.'], 403);
        }

        $validated = $request->validate([
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'integer|exists:poll_options,id',
        ]);

        $user = $request->user();

        // Vérifier si l'utilisateur a déjà voté
        $existingVote = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote && !$poll->allow_vote_change) {
            return response()->json(['message' => 'Already voted.'], 403);
        }

        // Supprimer les anciens votes si changement autorisé
        if ($existingVote) {
            PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->delete();
        }

        // Choix unique : prendre seulement le premier
        $optionIds = $poll->allow_multiple_choices
            ? $validated['option_ids']
            : [$validated['option_ids'][0]];

        foreach ($optionIds as $optionId) {
            PollVote::create([
                'poll_id' => $poll->id,
                'user_id' => $user->id,
                'poll_option_id' => $optionId,
            ]);
        }

        return response()->json(['message' => 'Vote recorded.']);
    }

    public function results(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        $isOwner = $user && $poll->user_id === $user->id;

        if (!$poll->results_public && !$isOwner) {
            return response()->json(['message' => 'Results are private.'], 403);
        }

        return response()->json([
            'poll' => $poll,
            'total_votes' => $poll->votes()->count(),
        ]);
    }

    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }
}
