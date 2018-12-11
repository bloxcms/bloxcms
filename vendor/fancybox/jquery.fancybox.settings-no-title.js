/* 
 * Init and settings 
 * @example <a data-fancybox-group="gallery1" href=".../1.jpg"></a>
 * @example echo'<a data-fancybox-group="block-'.$block.'" href=".../1.jpg"></a>';
 */

$(document).ready(function() {
    $('[data-fancybox-group]').fancybox({helpers:{title: null}});
});



    