<?php
return [
//'pseudopage-is-not-registered' => 'Псевдостраница %s не зарегистрирована в структуре сайта',
'failed-to-write-to-pages' => 'Не удалось записать %s в таблицу pages',
'failed-to-write-to-pseudopages' => 'Не удалось записать %s в таблицу pseudopages',
'first-arg-is-empty' => 'В методе Router::convert() первый аргумент пуст',
'infinite-loop-in-truncate-alias' => 'Вероятно случился бесконечный цикл в методе Router::truncateAlias(%s)',
'infinite-loop-in-uniquize-alias' => 'Вероятно случился бесконечный цикл в методе Router::uniquizeAlias(%s)', //Probably happened an infinite loop in the function
'main-block-id-is-changed' => 'У страницы поменялся основной контентный блок, к которому производятся прямые запросы через URL (был: %s, стал: %s). Это может вызвать вызывать неправильную работу ЧПУ-ссылок для штатных страниц с пагинацией или с параметрическими запросами в хвостах',
'no-alias-for-page' => 'Отсутствует алиас для страницы %s',
'nonstandard-param' => 'В аргументе $phref метода Router::convert() имеется нестандартный url-параметр %s. Чтобы разрешить этот параметр, добавьте имя этого параметра в аргумент-массив $params',
'no-page' => 'Страница %d не существует! Перейти %sна главную страницу%s',
'no-parent-key-in-pseudopages' => 'В таблице pseudopages не указан parent-key для ключа: %s',
'no-parent-page-info' => 'Родительская страница для текущей страницы была назначена вручную, но данных о родительской странице нет',
'no-request-value' => 'Попытка обработать запрос без значения %s в Router::convert(%s)',
'other-inputs' => 'Другие входные данные: %s',
'pages-update-error' => 'Произошла ошибка при обновлении таблицы pages',
'pseudopages-insert-error' => 'Произошла ошибка при вставке новой строки в таблицу pseudopages',
'pseudopages-update-error' => 'Произошла ошибка при обновлении таблицы pseudopages запросом: %s',
'search-mark' => 'Ищите в HTML-коде странички метку: #conversionError',
'should-be-relative-parametric-url' => 'Некорректный аргумент Router::convert(%s) — должен стоять относительный параметрический URL',
];