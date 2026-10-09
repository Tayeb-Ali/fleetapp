<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Simple username login (Auth::attempt on username). */
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public function login(): mixed
    {
        $this->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $username = strtolower(trim($this->username));

        if (! Auth::attempt(['username' => $username, 'password' => $this->password], true)) {
            // Fallback: allow email as username for default Laravel users.
            if (! Auth::attempt(['email' => $username, 'password' => $this->password], true)) {
                $this->addError('username', 'Wrong username or password');

                return null;
            }
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && isset($user->active) && ! $user->active) {
            Auth::logout();
            $this->addError('username', 'Account disabled');

            return null;
        }

        request()->session()->regenerate();

        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
