/** 
 * Scroll to the old position on the page after editing and other admin actions
 * Works when clicking standart edit button [data-blox-edit-href]. Add the class .blox-maintain-scroll for other custom links 
 * @example Code in start page
 *     <?php 
 *     Blox::addToFoot(Blox::info('cms','url').'/assets/blox.maintain-scroll.js'); // This should be included only on start page.
 *     echo '<a class="blox-maintain-scroll" href="...">...</a>'; // Edit button or other admin links.
 *     ?>
 * @example Direct use 
 *      <script>Blox.maintainScroll()</script>
 * @todo // Make "left page data" instead of wname.  LeftPageInfo   ---PageInfo   --currWindowInfo  ---WindowPageInfo ---LeftPageInfo
 */
$(function() {
    
    var $isPage = (typeof Blox.isPage === "function") // or !== "undefined"
        ? Blox.isPage()
        : true
    ;// Since "blox.maintain-scroll.js" may be used on nonauthorized pages i.e. without "blox.public.js"
    
    
    
    if ($isPage) {
        var wname = '';
        // Restore position
        if (window.name) { //if (!document.referrer.includes('?page=') && window.name) { // Coming from admin mode not from page
            wname = window.name;
            var wdata = JSON.parse(sessionStorage.getItem(wname) || '{}');
            if (wdata.pos) // Scroll by offset
                $(window).scrollTop(wdata.pos);
            else if (wdata.hash && !location.hash) // Scroll by hash
                location.hash = wdata.hash;
        }
        // Reset position
        sessionStorage.removeItem(wname);
        // Store position
        $('[data-blox-edit-href], .blox-maintain-scroll').click(function() {
            Blox.maintainScroll()
        });

        Blox.maintainScroll = function() {
            var wdata = {};
            if (!wname) {
                // Gen random name for window
                var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                for (var i=0; i < 16; i++ )
                    wname += chars.charAt(Math.floor(Math.random() * chars.length));
                window.name = wname;
            }
            if ($(window).scrollTop()) { // Scroll by offset
                wdata.pos = $(window).scrollTop()  //TODO: "scrollTop" does not work if window is scrolled to the most bottom
            } else if (location.hash)  { // Scroll by hash
                wdata.hash = location.hash;
            }
            sessionStorage.setItem(wname, JSON.stringify(wdata));
        }
        
    };
});
 
 