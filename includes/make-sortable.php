<?php
/** 
 * @todo Save recs in {} like in eskulap > shop/admin/goods/box.tpl but not in []
 
 */
Blox::addToFoot(Blox::info('cms','url').'/assets/jquery-1.js');
Blox::addToFoot(Blox::info('cms','url').'/assets/jquery-ui.js', ['after'=>'jquery-1']);
Blox::addToFoot('
    <script>
    $(function() {     
        $("#'.$sortable_tableId.' tbody").sortable({handle:".handle"});
        $("#'.$sortable_tableId.'").disableSelection();
        $("form#'.$sortable_formId.'").submit(function() {
            $("#'.$sortable_inputId.'").val(function() {
            	var columns = [];
            	$("#'.$sortable_tableId.' > tbody").each(function() {
            		columns.push($(this).sortable("toArray").join(","))
            	});
            	return columns; /* now it is string */
            })
        })
    })
    </script>'
);