<?php
# DEPRECATED. USED AS SAMPLE ONLY
if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
    Blox::execute('?error-document&code=403');

Sql::query('CREATE TABLE IF NOT EXISTS '.Blox::info('db','prefix').'editbuttonsetting (
    `block-id` '.Admin::reduceToSqlType('block', true).' UNIQUE,
    `top` SMALLINT,
    `left` SMALLINT,
    `z-index` INT
) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        
$data = Data::get(Blox::info('db','prefix').'editbuttonsetting', ['block-id'=>(int)$_GET['block']]);
$template->assign('data', $data);
include Blox::info('cms','dir').'/includes/buttons-submit.php';
include Blox::info('cms','dir').'/includes/display.php';