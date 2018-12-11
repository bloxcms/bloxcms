/* 
 * Init and settings 
 * @example <a data-fancybox-group="gallery1" href=".../1.jpg">...</a>
 * @example <a data-fancybox-group="gallery1" href=""><img src=".../1.jpg"></a> // If [href] is not specified, then will be img[src] enlarged. If a[title] is not specified, then will be img[alt] used
 * @example echo'<a data-fancybox-group="block-'.$block.'" href=".../1.jpg">...</a>';
 */

$(document).ready(function() {
    $('[data-fancybox-group]').fancybox();
    $('.fancybox').fancybox(); // Deprecated since 2017-05-10

    $('[data-fancybox-group]').each(function() {
        var $img = $(this).find('img');
        if (!$(this).attr('href')) /*if a[href] is not specified, then will be img[src] enlarged*/
            $(this).attr('href', $img.attr('src'));
        if (!$(this).attr('title')) /* if a[title] is not specified, then will be img[alt] used */
            $(this).attr('title', $img.attr('alt'));
    });
});



    