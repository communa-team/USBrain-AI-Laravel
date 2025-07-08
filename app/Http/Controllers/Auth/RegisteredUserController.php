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
use App\Http\Requests\Auth\UserRequest;

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
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        // $apiUrl = $_ENV['LETTA_API_URL'] ?? null;
        $apiToken = $_ENV['LETTA_API_TOKEN'] ?? null;

        $client = new Client($apiToken);
        $lettaAgent = $client->agents()->create([
            'name' => "OrthodoxTextsAgent_" . $user->nickname,
            'agent_type' => "voice_convo_agent",
            'memory_blocks' => [
                ['label' => 'human', 'value' => 'Пользователь ' . $user->name . ' желает поолучить ответы на вопросы на основе православных текстов из archival_memory.'],
                ['label' => 'persona', 'value' => 'Православный ассистент, который отвечает на вопросы пользователя на основе православных текстов из archival_memory.'],
            ],
            // 'source_ids' => ['source-ed537329-ca09-40c9-a395-905a4f14bf75'], // все тексты ()
            // Letta обрабатывает файл с None как строку. В контексте файлов это чаще всего происходит в line_chunker.py:44 , где вызывается text.splitlines().
            // 'source_ids' => ['source-c3f918ad-3c90-44ab-b392-36b8c0eb9de4'], // один текст
            'model' => 'letta/letta-free',
            'embedding' => 'letta/letta-free',
        ]);

        $agent = Agent::create([
            'name' => "OrthodoxTextsAgent_" . $user->nickname,
            // "agent_type" => "memgpt_agent",
            'agent_type' => "voice_convo_agent",
            'user_id' => $user->id,
            'letta_agent_id' => $lettaAgent['id'],
        ]);
        // echo "Agent:\n" . json_encode($lettaAgent, JSON_PRETTY_PRINT) . "\n";

        event(new Registered($user));

        Auth::login($user);

        return to_route('home');
    }
}
