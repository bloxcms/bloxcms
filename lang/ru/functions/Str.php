<?php
return [
    # isValid()
    'not-email' => 'Некорректный адрес электронной почты',
    'not-password' => 'Пароль должен содержать от 4 до 24 латинский букв, цифр и спец.символов',
    'not-login' => 'Логин должен состоять из латинских букв, цифр, символа подчеркивания, дефиса и точки',
    'not-not-empty' => 'Нет данных',
    'not-tpl' => 'Некорректное имя шаблона',
    'not-phone' => 'Некорректный номер телефона',
    # declineWords()
    'not-enough-args' => 'В методе %s не хватает аргументов!',
    'no-decline-rules' => 'В методе Str::declineWords() нет правил для языка: %s',
    'decline-words' => function($prewords, $number, $afterwords=[], $options=[]) {
        /**
         * For russian ['Word after *1', 'Word after *2-*3-*4', 'Word after *5-*6-*7-*8-*9-*0-11-12-13-14-15-16-17-18-19']
         */
        $cases = [2, 0, 1, 1, 1, 2]; # List of keys of $prewords
        $getWord = function($number, $words, $cases) {
            return $words[
                ($number%100 > 4 && $number %100 < 20) 
                    ? 2 
                    : $cases[min($number%10, 5)]
            ];
        };
        if ($prewords)
            $str.= $getWord($number, $prewords, $cases);
        if (!$options['no-number'])
            $str.= ' '.$number;
        if ($afterwords)
            $str.= ' '.$getWord($number, $afterwords, $cases);
        return $str;
    },
    # sanitizeAlias()
    'digits-are-replaced' => 'В алиасах недопустимы строки, состоящие только из цифр. Строка: %s заменена на %s',
    # transliterate()
    'no-translit-array' => 'Отсутствует массив транслитерации для метода Str::transliterate()',
    'transliterations' => [
        'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'jo', 'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'j', 'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f', 'х'=>'h', 'ц'=>'c', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch', 'ъ'=>'', 'ы'=>'y', 'ь'=>'', 'э'=>'e', 'ю'=>'ju', 'я'=>'ja',
        'А'=>'A', 'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ё'=>'JO', 'Ж'=>'ZH', 'З'=>'Z', 'И'=>'I', 'Й'=>'J', 'К'=>'K', 'Л'=>'L', 'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R', 'С'=>'S', 'Т'=>'T', 'У'=>'U', 'Ф'=>'F', 'Х'=>'H', 'Ц'=>'C', 'Ч'=>'CH', 'Ш'=>'SH', 'Щ'=>'SCH', 'Ъ'=>'', 'Ы'=>'Y', 'Ь'=>'', 'Э'=>'E', 'Ю'=>'JU', 'Я'=>'JA',
    ],
    'replacements' => [ # Do not use "~" in key, use "\~"
        '~(\d)h(\d)~u' => '$1x$2', # букву h(то есть, русс. х) между числами превратить в икс (640х380)
        /**
         * @todo
         *     Доп. преобразования: ij=>i (информация), yj=>y (добрыя?). Но только чтобы не последнее j (metallorezhuschij)        
         *     подъем - podjom, podjem  / ъе - je
         *     вьюга - vjjuga, vjuga (твердый или мягкий знак перед гласными как j)
         *     галька - gal'ka-xx
         *     бу\Деновка
         */
    ],
];