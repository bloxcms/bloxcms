<?php
    /** External vars:
     *      $dat
     *      $editButtonClass
     *      $editButtonTitle
     *      $filtersQuery
     *      $pagehrefQuery
     *      $regularId
     *      $ro
     *      $srcBlockId
     *      $tdd
     *      $tpl
     */

    Blox::includeTerms($terms);
        
    # Because when you search by record number, may be appeared the highlight tag
    if (Request::get($regularId,'search','highlight') && Request::get($regularId,'search','patterns','rec'))
        $dat['rec'] = Text::stripTags($dat['rec'],'strip-quotes');
    $editButtonClass2 = '';
    $editButtonTitle2 = $terms['to-edit-rec'].' ('.$tpl.')';
    if (isset($tdd['params']['hiding-field']) && $dat[$tdd['params']['hiding-field']]) {
        $editButtonClass2 .= ' blox-hidden';
        $editButtonTitle2 .= '. '.$terms['hidden'];
        $tab[$ro]['hidden'] = true;
    }
    if ($srcBlockId == $_SESSION['Blox']['last-edited']['src-block-id'] && $dat['rec'] == $_SESSION['Blox']['last-edited']['rec-id']) {
        $editButtonClass2 .= ' blox-last-edited';
        $editButtonTitle2 .= '. '.$terms['last-edited'];
        $tab[$ro]['last-edited'] = true;
    }
    if ($tdd['params']['no-edit-buttons'])
        $editButtonTitle2 .= '. '.$terms['no-edit-buttons'];
    
    $buttElem = ($tdd['params']['span-edit-buttons']) ? 'span' : 'a';
    # $filtersQuery comes from Blox::getBlockHtm.php. It is necessary to pass to buttons in includes/output-multirec-buttons.php
    $tab[$ro]['edit-href'] = '?edit&block='.$regularId.$filtersQuery.'&rec='.$dat['rec'].$pagehrefQuery;
    $tab[$ro]['delete-href'] = '?recs-delete&which='.$dat['rec'].'&block='.$regularId.$filtersQuery.'&rec='.$dat['rec'].$pagehrefQuery;
    $aa = $editButtonClass.$editButtonClass2;
    $tab[$ro]['edit'] = '<!--noindex--><'.$buttElem.' class="'.$aa.'"'.$editButtonStyleAttr.' href="'.$tab[$ro]['edit-href'].'" title="'.$editButtonTitle.$editButtonTitle2.'" rel="nofollow"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/edit-button-edit-rec.png" alt="&equiv;" /></'.$buttElem.'><!--/noindex-->';
    $aa = 'blox-edit-button blox-delete-rec blox-maintain-scroll';
    $tab[$ro]['delete'] = '<!--noindex--><'.$buttElem.' class="'.$aa.'" href="'.$tab[$ro]['delete-href'].'" title="'.$terms['delete-rec'].' ('.$tpl.')" rel="nofollow"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/edit-button-delete-rec.png" alt="&times;" /></'.$buttElem.'><!--/noindex-->';
