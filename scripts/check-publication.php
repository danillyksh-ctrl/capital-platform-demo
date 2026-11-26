<?php

/** Проверяет только список файлов, предлагаемых Git к публикации, без вывода содержимого секретов. */
$root = dirname(__DIR__);
chdir($root);
$output = shell_exec('git ls-files -z --cached --others --exclude-standard');
if ($output === null) {
    fwrite(STDERR, "Не удалось получить список файлов Git.\n");
    exit(1);
}
$errors = [];
$files = array_unique(array_filter(explode("\0", $output)));
foreach ($files as $file) {
    if (preg_match('~^(?:\.tools|vendor|node_modules|artifacts|\.aws|\.codex)/|(?:^|/)(?:auth\.json|\.env)(?:$|\.)|\.(?:sqlite\d*|db|dump|sql|pem|p12|pfx|key|log)$~i', $file) && $file !== '.env.example') {
        $errors[] = "$file: запрещённый тип публикуемого файла";
    }
    if (! is_file($file) || preg_match('/\.(png|jpg|ico)$/i', $file)) {
        continue;
    }
    $content = file_get_contents($file);
    if (preg_match('~[A-Z]:[\\\\/]Users[\\\\/]|/(?:Users|home)/[a-zA-Z0-9_-]+/~', $content)) {
        $errors[] = "$file: абсолютный путь пользователя";
    }
    if (preg_match('~-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|gh[pousr]_[a-zA-Z0-9]{30,}|github_pat_[a-zA-Z0-9_]{30,}|AKIA[0-9A-Z]{16}~', $content)) {
        $errors[] = "$file: возможный секрет";
    }
    if ($file === '.env.example' && preg_match('/^(APP_KEY|DB_PASSWORD|DEMO_PASSWORD)=.+$/m', $content)) {
        $errors[] = "$file: секрет должен быть пустым";
    }
    if ($file === 'README.md') {
        preg_match_all('~!\[[^\]]*\]\((docs/screenshots/[^)]+)\)~', $content, $images);
        foreach ($images[1] as $image) {
            if (! is_file($image)) {
                $errors[] = "$image: отсутствует скриншот из README";
            }
        }
    }
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}
echo 'PASS: '.count($files)." файлов; запрещённых путей и известных сигнатур секретов не обнаружено.\n";
echo "Это проверка правил и сигнатур, она не заменяет ручную проверку.\n";
