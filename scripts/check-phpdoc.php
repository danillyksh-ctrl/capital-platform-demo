<?php

/** Проверяет русскоязычный PHPDoc классов и методов собственного кода. */
$root = dirname(__DIR__);
$errors = [];
foreach (['app', 'database', 'tests'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $tokens = token_get_all(file_get_contents($file->getPathname()));
        $doc = null;
        $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL, T_READONLY];
        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                if (! in_array($token, ['&', '(', ')'], true)) {
                    $doc = null;
                }

                continue;
            }
            [$type, $text, $line] = $token;
            if ($type === T_DOC_COMMENT) {
                $doc = $text;

                continue;
            }
            if (in_array($type, [T_WHITESPACE, T_COMMENT, ...$modifiers], true)) {
                continue;
            }
            if ($type === T_FUNCTION) {
                $following = $index + 1;
                while (isset($tokens[$following]) && is_array($tokens[$following]) && $tokens[$following][0] === T_WHITESPACE) {
                    $following++;
                }
                // Анонимные функции и замыкания не требуют PHPDoc метода.
                if (isset($tokens[$following]) && is_array($tokens[$following]) && $tokens[$following][0] === T_STRING && ! preg_match('/[А-Яа-яЁё]/u', $doc ?? '')) {
                    $errors[] = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname()).":$line: отсутствует русский PHPDoc метода";
                }
            }
            if ($type === T_CLASS) {
                $previous = $index - 1;
                while ($previous >= 0 && is_array($tokens[$previous]) && $tokens[$previous][0] === T_WHITESPACE) {
                    $previous--;
                }
                // Ссылки SomeClass::class не являются объявлениями класса.
                if (! (is_array($tokens[$previous] ?? null) && in_array($tokens[$previous][0], [T_DOUBLE_COLON, T_NEW], true)) && ! preg_match('/[А-Яа-яЁё]/u', $doc ?? '')) {
                    $errors[] = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname()).":$line: отсутствует русский PHPDoc класса";
                }
            }
            $doc = null;
        }
    }
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}
echo "PASS: PHPDoc собственных классов и именованных методов написан на русском.\n";
