$(function() {
    /**/
    Blox.isPage = function() {
        var href = location.href.replace($('base').attr('href'), ''); // base relative url
        if (!href || '#'==href.charAt(0) || href.includes('?page=')) // home page or page // Href is always parametric in edit mode 
            return true;
    }
    
    /* Overlay loading icon on submit 
     * For loading icon that substitutes a part of page, see .bloxLoader() 
     */
    $('[data-blox-overlay-load-icon-target]').click(function() {
        $('body').addClass('blox-overlay-load-icon');
        // If a submit button fires ajax update (not the whole page) then force removing the class "blox-overlay-load-icon"
        $(document).ajaxComplete(function() {
            $('body').removeClass('blox-overlay-load-icon');
        });
    });
    $('a:not([href="#"]) .blox-bar a:not([data-blox-overlay-load-icon-target]), .blox-bar button:not([data-blox-overlay-load-icon-target]), .blox-edit button:not([data-blox-overlay-load-icon-target])').click(function() {
        $('body').addClass('blox-overlay-load-icon');
        $(document).ajaxComplete(function() {
            $('body').removeClass('blox-overlay-load-icon');
        });
    });
    
    /** 
     * Form 
     * Send form data as URL parameters.
     * Designed to work in conjunction with the class Query::
     * The initial URL is written in the attribute "action".
     * @example <form data-blox-method="get">. Other attributes are (method, id) unnecessary.
     * @todo Sorting parameters as in Query::build(). Now works the last parameter, even if before there is the same param. Now the order is determined by the fields order. If you need to put a param from attribute "action" after a specified param, then take out this parameter in a hidden field wihout encoding: <input type="hidden" name="pagehref" value="'.$pagehref.'" />
     */
    $('form[data-blox-method="get"]').submit(function(e) {
        e.preventDefault();
        var action = $(this).attr('action');
        var data = $(this).serialize();
        location.href = action + '&' + data; 
    });
    
    /**
     * Button for submitting a form. A button may lays outside of form
     * [data-blox-submit-selector] - Selector of the form
     * @example <button data-blox-submit-selector="#myForm">...</button>
     */    
    $('[data-blox-submit-selector]').click(function(e) {        
        e.preventDefault();
        // selector
        var target = $(this).data('blox-submit-selector');
        /** 
          * @resereved For attr data-blox-submit-params="{'aa':'bb'}" - Extra data represented in JSON format (single quotes allowed). Data will be converted to url params.
          * @todo Do not use Json = do derectly url params: data-blox-submit-params="aa=bb"
        // params
        var params = $(this).data('blox-submit-params');
        if (params) {
            var params2 = JSON.parse(params.replace(/'/g, '"')); // convert json string to object
            $(target).attr(
                'action', 
                $(target).attr('action') + '&' + $.param(params2)
            );
        }
        */
        // submit
        $(target).submit();        
    });
    
    
    
    
    /* Tooltip */
	$('.blox-tooltip')
    .mouseover(function(e) {
		var tip = $(this).attr('title');
        if (tip) {
    		$(this).attr('title','');
    		$(this).append('<div id="blox-tooltip-content">'+tip+'</div>');//Append the tooltip template and its value
    		$('#blox-tooltip-content').fadeIn('slow');
        }
        $(this).children('img').css({opacity:1}); //== $('img', this).css({opacity:1});        
	})
    .mouseout(function() {		
		$(this).attr('title',$('#blox-tooltip-content').html());//Put back the title attribute's value
		$(this).children('#blox-tooltip-content').remove();//Remove the appended tooltip template
        $(this).children('img').css({opacity:0.5});
	});
    //tooltip


    /** edit-button 
     * @deprecated [data-blox-edit-href] since 13.6.0
    */
    $('span.blox-edit-button, [data-blox-edit-href]').on('click', function(e) {
        e.preventDefault();
        location.href = $(this).attr('href') || $(this).data('blox-edit-href');
    });
    /** edit-button */
    $('.blox-edit-button').on('click', function(e) {
        $(this).addClass('blox-wait');
    });
    
    
        
    /**
     * Hot keys. How can add only a single key, not combination
     * @example	<span data-blox-shortcut-key="69" data-blox-shortcut-url="?site-settings">E</span>  i.e. put two data attributes [...-key] and [...-url] into any element
     * For the list of available key codes see the file: blox.shortcut.html
     */
    Blox.shortcuts = {};
    $('[data-blox-shortcut-key]').each(function() {
        Blox.shortcuts[$(this).data('blox-shortcut-key')] = $(this).data('blox-shortcut-url');
    });
    $('body').on('keyup', function(ev) {
        ev = ev || window.event;  // for IE
        var $url = Blox.shortcuts[ev.keyCode];
        if ($url) {
            /* Cancel if it is an input form */
            var el;
            if (ev.target)
                el=ev.target;
            else if (ev.srcElement)
                el=ev.srcElement;
            if (el.nodeType==3)
                el=el.parentNode;
            if (el.tagName == 'INPUT' || el.tagName == 'TEXTAREA' || el.tagName == 'SELECT')  // OPTION is nessessary?
                return;
            //
            if (Blox.isPage())
                Blox.maintainScroll();// Store position
            $('body').addClass('blox-overlay-load-icon'); 
            document.location.href = $url;
        }
    });
    
    
    $('.blox-select-menu li > a[href="#"]').on('click', function(e) {
        e.preventDefault();
    });
   
});
