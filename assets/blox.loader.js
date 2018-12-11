(function($) {       
    /* Loading icon by $(...).bloxLoader() */
    $.fn.bloxLoader = function() {
        var height = this.css('height');
        this.html('<div class="blox-load-icon"><div></div></div>');
        this.find('.blox-load-icon').css('height', height);
        return this;
    };
}(jQuery));