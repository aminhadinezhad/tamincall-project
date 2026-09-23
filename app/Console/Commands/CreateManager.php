<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * The first sign-in on a new server, and any manager added later. The password is typed, never
 * passed as an argument, so it does not stay in the shell history.
 */
class CreateManager extends Command
{
    protected $signature = 'tamin:manager';

    protected $description = 'ساختن حساب مدیر';

    public function handle(): int
    {
        $name = text('نام', required: true);
        $email = text('ایمیل (برای ورود)', required: true);
        $password = password('رمز عبور (حداقل ۸ نویسه)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->info("حساب مدیر ساخته شد: {$user->email}");

        return self::SUCCESS;
    }
}
