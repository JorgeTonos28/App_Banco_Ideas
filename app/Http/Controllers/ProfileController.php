<?php

namespace App\Http\Controllers;

use App\Models\Regional;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(?User $user = null): View
    {
        $targetUser = $user && $user->exists ? $user : auth()->user();

        // User stats
        $ideasCount = $targetUser->ideas()->where('visibility', 'public')->count();
        $implementedCount = $targetUser->ideas()->where('status', 'implementada')->count();
        $totalVotesReceived = $targetUser->ideas()->sum('votes_count');
        $ratingsGivenCount = $targetUser->ratings()->count();

        // Participation Score (calculated)
        $participationScore = ($ideasCount * 20) + ($totalVotesReceived * 2) + ($ratingsGivenCount * 5);

        // Recent public contributions
        $contributions = $targetUser->ideas()
            ->with(['category', 'tags'])
            ->where('visibility', 'public')
            ->latest()
            ->take(8)
            ->get();

        $badges = $targetUser->badges;

        return view('profile.show', compact(
            'targetUser',
            'ideasCount',
            'implementedCount',
            'totalVotesReceived',
            'participationScore',
            'contributions',
            'badges'
        ));
    }

    public function edit(): View
    {
        $user = auth()->user();
        $regionals = Regional::where('is_active', true)->orderBy('order')->get();

        return view('profile.edit', compact('user', 'regionals'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'avatar_cropped' => ['nullable', 'string'],
        ]);

        if (!empty($validated['regional_id'])) {
            $reg = Regional::find($validated['regional_id']);
            $validated['regional'] = $reg?->full_name;
        }

        // Process cropped avatar from Interactive Canvas
        if (!empty($request->avatar_cropped) && str_starts_with($request->avatar_cropped, 'data:image')) {
            $imageData = $request->avatar_cropped;
            @list($type, $data) = explode(';', $imageData);
            @list(, $data)      = explode(',', $data);
            
            if ($data) {
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $filename = 'avatars/' . Str::random(40) . '.jpg';
                    Storage::disk('public')->put($filename, $decoded);

                    if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                    $validated['avatar'] = $filename;
                }
            }
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        unset($validated['avatar_cropped']);

        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Tu perfil y foto institucional han sido actualizados con éxito.');
    }
}
