
// Select
function submitBySelectListItem(field) {
    document.getElementById('select-list-submit-field').value=field;
    document.getElementById('dat-edit-form').submit();
}

// Stapms
$(window).load(function() 
{
    $('input[name^="dat"][type="file"]').change(function() {
        // The field on which you clicked
        var divstamp = $(this).parent().parent().find('div.stamp'); // Find the stamp box
        divstamp.slideDown('slow'); // Open
        var origSrcField = divstamp.attr('data-origSrcField');
        var field = $(this).attr('name').match(/\[(\d+)\]/)[1];
        // Dependent fields
        if (origSrcField) { // Clicked on the field, which itself is dependent
            var display = false;
            $('[data-origSrcField="'+origSrcField+'"]').each(function() {
                // for each dependent field
                if (!display) {
                    if ($(this).parent().find('input[type="file"]').attr('name').match(/\[(\d+)\]/)[1] == field) // clicked field
                        display = true; 
                } else
                    $(this).slideDown('slow');// Open the rest
            });
        } else { // Original itself
            $('[data-origSrcField="'+field+'"]').each(function() {
                $(this).slideDown('slow');// Open all dependant images
            });
        }
    });

    // Control by "placement" fields
    $('[data-placement]').change(function() {
        var atLeastOne = false;
        var moreThanOne = false;
        var isHorizMedian = false;
        var isNotHorizMedian = false;
        var isVertMedian = false;
        var isNotVertMedian = false;

        $('[data-placement]').each(function(i) {
            if ($(this).attr('checked')) {
                if (atLeastOne)
                    moreThanOne = true;
                atLeastOne = true;
                // 0 1 2
                // 3 4 5
                // 6 7 8
                if (i==3 || i==4 || i==5)
                    isHorizMedian = true;
                else
                    isNotHorizMedian = true;

                if (i==1 || i==4 || i==7)
                    isVertMedian = true;
                else
                    isNotVertMedian = true;
            }
        });
        
        // at least one check
        if (atLeastOne) {
            $('[data-stamp]').prop('disabled',false); // show all fields
            if (moreThanOne) {
                $('[data-stamp=stretch]').prop('checked',false);
                $('[data-stamp=stretch]').prop('disabled',true);//Hide "stretch" field
            }

            if (isHorizMedian && !isNotHorizMedian)
                $('[data-stamp=indents_vertical]').prop('disabled',true); // Hide vertical indent

            if (isVertMedian && !isNotVertMedian)
                $('[data-stamp=indents_horizontal]').prop('disabled',true);//Hide horizontal indent
        } else {
            $('[data-stamp]').prop('disabled',true);//Hide all fields
        }
    }).change();

    // Control by "stretch"
    $('[data-stamp=stretch]').change(function()
    {
        if ($(this).attr('checked'))
        {
            $('[data-stamp=scale]').prop('disabled',true); //Hide the field
            $('[data-stamp=indents_horizontal]').prop('disabled',true); //Hide the field
            $('[data-stamp=indents_vertical]').prop('disabled',true); //Hide the field
        } else {
            $('[data-stamp=scale]').prop('disabled',false);
            $('[data-stamp=indents_horizontal]').prop('disabled',false);
            $('[data-stamp=indents_vertical]').prop('disabled',false);
        }
    }).change();
});

$(document).ready(function() 
{ 
    // If a field NULL is checked, clear an input field. "Multi" too, except "data["
    $('[name^="null-dat["]').click(function() {
        var inpt = $(this).parent('label').parent('td').children('input').first().val('');
    });
    // If an input field is checked, clear Null field
    $('[name^="dat["]').click(function() {
        $(this).siblings('label').children('input').prop('checked', false);
    });
});