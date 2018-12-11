/**
 * @see http://forum.jquery.com/topic/save-position-of-draggable-item
 *
 * @todo Do storage key from window name (not by pathname) as in blox.maintain-scroll.js
 */

$(function() {
    $('#blox-bar-fixed').draggable()
});
var fixedBarPosition = JSON.parse(localStorage.getItem('fixedBarPosition' + location.pathname) || '{}');

$(function() {
    var panel = $('#blox-bar-fixed');
    panel.css(fixedBarPosition);
    panel.draggable({
        containment: 'document',//window body
        scroll: false,
        stop: function (event, ui) {
            localStorage.setItem(
                'fixedBarPosition' + location.pathname, 
                JSON.stringify(ui.position)
            );
        }
    });

    /**
     * @todo Flyout. Do hotkey for it too
    $('#blox-slide-bar').click(function(){ // see. blox.public.css
        var leftPosition = '0';
        panel.css('left', leftPosition);
        localStorage.setItem(
            'fixedBarPosition'+ location.pathname, 
            JSON.stringify({left: leftPosition, top: panel.css('top')})
        );
    });
    */
});
