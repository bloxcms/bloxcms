<?php
return [
# addXcode()
'no-file' => 'Метод Blox::addTo%s(). Файл %s не существует',
# dressCodeWithTags()
'arg-has-not-ext'=>'Аргумент функции addTo%s() не имеет расширения css или js. Перепишите этот аргумент одним из способов: (&lt;link href="%s" rel="stylesheet" /&gt;) или (&lt;script src="%s"&gt;&lt;/script&gt;)',    
'url-not-exists' => 'URL: %s, примененный в методе Blox::addTo%s(), не существует!',
# shiftAdjacents()
'no-file-links' => 'При вызове метода Blox::addTo%s() не удалось найти подключение файлов: %s',
# getBlockHtm()
'assign' => 'Назначить',
'autoincrement-and-part' => 'В дескрипторе блока %s задан режим autoincrement, однако на данной странице произведен также part-запрос. В таком режиме autoincrement работать не будет. Задайте в шаблоне только один способ вывода блока по частям.',
'multirec-edit-button-title' => 'Редактировать блок',
'new-rec' => 'Добавить новую запись',
'no-data' => 'Нередактируемый шаблон',
'required-pick-sess-is-empty' => 'Для отображения записей блока %s, должны поступить запросы (%d шт.)',
'select-tpl' => 'Выберите шаблон из списка в панели',
'to-assign-tpl' => 'Назначить шаблон %s?',
'to-delegate' => 'С помощью панели управления (в верхней части страницы) вы можете также делегировать сюда уже существующий экземпляр блока с таким шаблоном или выбрать другой шаблон.',
'tpl-not-assigned' => 'Блоку не назначен шаблон. Назначить?',
'tpl-not-exists' => 'Однако такого шаблона на сайте нет.',
'blank-terms' => [
    'select-outer-tpl' => 'Выберите шаблон внешнего блока страницы из списка в панели',
    'tpl-is-selected' => 'Шаблон выбран. Можете щелкнуть по кнопке "Назначить".',
    'delegate' => 'С помощью панели Вы можете также делегировать сюда уже существующий экземпляр блока с шаблоном:',
    'no-outer-tpl' => 'Странице (внешнему блоку страницы) еще не назначен шаблон.',
    'visitor-mode' => 'Чтобы начать назначение шаблона, отключите режим посетителя!',
    'select-tpl' => 'Выбрать шаблон',
],
# replaceBlockIdsByHtm()
'auto-assign' => 'Новому блоку %s был автоматически назначен шаблон %s', 
'auto-assign-for-outer' => 'Новому внешнему блоку %s для целевой страницы только что созданной ссылки %s был автоматически назначен шаблон %s', 
'failed-block-id' => 'Неудачная попытка создать блок: %s', //Failed `block-id` generating
'failed-delegating' => 'Не удалось делегировать блок %s для регулярного блока %s', 
'failed-tpl' => 'Не удалось назначить шаблон %s. Проверьте элемент массива %s в файле %s', //Failed to assign the template
'failed-tpl-for-outer' => 'Не удалось назначить шаблон %s новому внешнему блоку. Проверьте элемент массива %s в файле %s', 
'gen-block-id-branch' => 'Сработала ветка генерации `block-id` в Admin::replaceBlockIdsByHtm() для %s из шаблона %s. Если это предупреждение не исчезает, нужно принять меры.', # Admin::genBlockId() branch is run in template %s for %s
'hide-link' => 'Ссылка на страницу %s в блоке %s скрыта, так как целевая страницы скрыта', 
'no-auto-tpl' => 'В шаблоне %s (поле %s) предусмотрен блок с шаблоном по умолчанию %s. Однако такого шаблона на сайте нет.',
# getByJson()
'json-error' => 'В json-конфигурации обнаружена ошибка:',
'json-decode-not-exists' => 'PHP-функция <b>json_decode()</b> на сервере не существует!',
];
