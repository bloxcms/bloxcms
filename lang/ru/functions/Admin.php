<?php
return [
# assignNewTpl.php()
'no-tpl-file' => 'Отсутствует файл шаблона:',
# delegate()
'not-updated' => 'Запрос (%s) не произвел никакого действия, скорее всего из-за отсутствия записи. Попробуйте сначала назначить новый шаблон без делегирования',
# genBlockId()
'failed-block-id-gen' => 'Не удалось сгенерировать номер блока', //Failed to generate the block number
# checkSortByColumnsFilters()
'invalid-request' => 'Недопустимый запрос:',
'undefined-sort' => 'Sort-запрос с неопределенным направлением сортировки при сохранении сортировки запрещен',
'no-pick-request' => 'Отсутствует pick-запрос, хотя ключевые поля имеются',
'no-param' => 'Недопустимый pick-запрос, так как не задан параметр:',
'invalid-pick-request' => 'Недопустимый в данном скрипте pick-запрос к полю',
'invalid-operator' => 'В pick-запроса недопустимый оператор:',
# getPromptsHtm()
'error-log' => 'Журнал ошибок',
'debug-log' => 'Журнал отладки',
# promptLog()
'display-log' => 'посмотреть',
'remove-log' => 'очистить',
# removeRec()
'cannot-remove-rec-of-pseudopages' => 'Не удалось удалить строку таблицы pseudopages с key=%s',
# resetBlock()
'cannot-remove-rec-of-pseudopages-2' => 'Не удалось удалить строку таблицы pseudopages с key=%s',
'no-block-id' => 'Отсутствует номер блока в Admin::resetBlock()', //BlockId is empty in resetBlock($blockId)
# isHtmValid()
'pieces' => 'шт',
'extra-tags-1' => 'Лишние теги: %s',
'extra-tags-2' => '. Блок %s, запись:%d, поле:%d. Для исправления бывает достаточно открыть запись в режиме визульного редактора и пересохранить ее. %sОткрыть%s.',
# getBlockButton()
'assign-tpl'=>'Назначить блоку шаблон',
'edit-block' => 'Редактировать блок', # and for getBlockHtm()
'no-edit-buttons' => 'Кнопка видна только для админа',
];
