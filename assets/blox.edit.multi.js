$(document).ready(function() 
{ 
    // Create new record: submit a form via Ajax and open the form again
    $('button[value="add-new-rec"]').click(function() {
        $("#dat-edit-form").css('opacity','0.5'); 
        $("#dat-edit-form").ajaxForm({ 
            success: function(){
                $('button[value="add-new-rec"]').prop('disabled', true);
                Blox.ajax(
                    $('input#add-new-rec-src').val(),
                    "multi-edit", 'enableNewRecButton()'
                );
            }
        });
    });
    // Normal way to submit a form (not Ajax) by canceling ajaxForm with the main button 
    $('button[value="ok"]').click(function(){
        $("#dat-edit-form").ajaxFormUnbind();            
    });

    // If a field NULL is checked, clear an input field. "Single" too, except "data["
    $('#dat-edit-form').on('click', '[name^="null-data["]', function() {
        $(this).parent('label').parent('td').children('input').first().val('');
    });
    // If an input field is checked, clear Null field
    $('#dat-edit-form').on('click','[name^="data["]', function() {
        $(this).siblings('label').children('input').prop('checked', false);
    });
});

function enableNewRecButton() 
{
   $('#dat-edit-form').fadeTo(300, 1.0);
   $('button[value="add-new-rec"]').prop('disabled', false);
   $('.blox-edit form').css('opacity','1.0'); 
}

// Select
function submitBySelectListItem(field, recId)
{
    document.getElementById('select-list-submit-field').value=field;
    document.getElementById('select-list-submit-rec').value=recId;
    document.getElementById('dat-edit-form').submit();
}
