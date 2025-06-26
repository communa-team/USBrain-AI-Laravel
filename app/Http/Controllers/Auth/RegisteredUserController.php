<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Letta\Client;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $apiUrl = $_ENV['LETTA_API_URL'] ?? null;
        $apiToken = $_ENV['LETTA_API_TOKEN'] ?? null;

        $client = new Client($apiToken, $apiUrl);
        $lettaAgent = $client->agents()->create([
            'name' => $user->name,
            'description' => 'Agent for user ' . $user->name,
            'identity' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
        if (!$lettaAgent || !isset($lettaAgent->id)) {
            throw new \Exception('Failed to create Letta agent');
        }
        $agent = Agent::create([
            'user_id' => $user->id,
            'letta_agent_id' => $lettaAgent->id,
            'name' => $lettaAgent->name,
        ]);
        echo "Agent:\n" . json_encode($lettaAgent, JSON_PRETTY_PRINT) . "\n";

        event(new Registered($user));

        Auth::login($user);

        return to_route('home');
    }
}
