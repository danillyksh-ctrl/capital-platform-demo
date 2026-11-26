<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Проверяет настоящую SMTP-доставку уведомлений в локальный Mailpit без вывода токенов. */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment(['local', 'testing']) || config('mail.default') !== 'smtp' || ! in_array(config('mail.mailers.smtp.host'), ['mailpit', 'localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('Проверка разрешена только для локального SMTP/Mailpit.');
}
$mailpitUrl = rtrim(getenv('MAILPIT_URL') ?: 'http://mailpit:8025', '/');
if (! in_array(parse_url($mailpitUrl, PHP_URL_HOST), ['mailpit', 'localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('Mailpit должен быть доступен только по локальному адресу.');
}
$user = User::create([
    'name' => 'Проверка почты',
    'email' => 'mail-check-'.Str::uuid().'@example.test',
    'password' => bin2hex(random_bytes(24)),
]);
try {
    $user->sendEmailVerificationNotification();
    $verification = Http::timeout(10)->retry(5, 300)->get($mailpitUrl.'/view/latest.txt', ['query' => 'to:'.$user->email])->throw()->body();
    if (! str_contains($verification, '/verify-email/'.$user->id.'/') || ! str_contains($verification, 'signature=')) {
        throw new RuntimeException('Mailpit не получил корректное письмо подтверждения.');
    }
    if (Password::sendResetLink(['email' => $user->email]) !== Password::RESET_LINK_SENT) {
        throw new RuntimeException('Password broker не отправил письмо восстановления.');
    }
    $reset = Http::timeout(10)->retry(5, 300)->get($mailpitUrl.'/view/latest.txt', ['query' => 'to:'.$user->email])->throw()->body();
    if (! str_contains($reset, '/reset-password/') || ! str_contains($reset, urlencode($user->email))) {
        throw new RuntimeException('Mailpit не получил корректное письмо восстановления.');
    }
    echo "PASS: SMTP/Mailpit получил письма подтверждения email и восстановления пароля с корректными ссылками.\n";
} finally {
    DB::table('password_reset_tokens')->where('email', $user->email)->delete();
    $user->delete();
}
