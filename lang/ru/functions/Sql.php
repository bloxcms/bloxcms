<?php
return [
'already-connected-to-db' => 'База данных уже подключена — метод Sql::setDb() вызыван более одного раза!',
//'cannot-connect' => 'Не удалось подключиться к базе данных. Неверно указан хост, пользователь или пароль', # "Can't connect to database. Host, user name or user's password are invalid"
'not-allowed-type-of-param' => 'В методе Sql::query(%s, ...) в элементе с ключом %s второго аргумента (массива) имеется данное запрещенного типа: %s',
'incorrect-identificator' => 'Имя SQL идентификатора: %s некорректно!',//'The sql identifiers name: %s is invalid'
'incorrect-tpl-name' => 'В имени шаблона %s обнаружены запрещенные символы: %s',
'no-placeholders-data' => 'Отсутствуют данные для плейсхолдеров: %s в sql-выражении: %s',
'no-params' => 'Применяется небезопасный sql-запрос: %s. Его необходимо параметризовать.',
'double-parameterization' => 'SQL-запрос параметризован двумя способами: с помощью метода Sql::parameterize() и с помощью массива $params — это запрещено. Запрос: %s',
'one-by-one-queries' => 'Следующие SQL-выражения выполняются подряд более одного раза (указано количество повторений запроса). Для этих запросов желательно использовать подготовленные выражения.',
'repeated-params' => 'Следующие SQL-запросы выполняются более одного раза (указано количество повторений запросов с одинаковым выражением и параметрами). Желательно исключить их повторное выполнение путем использования результатов самого первого запроса.',
];
