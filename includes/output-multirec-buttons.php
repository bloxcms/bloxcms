<?php
    /* 
    # .button samples  
    echo'<br><br><br>
    <a class="button button-xsmall" href="">Segsaef<br>dehssshs</a> 
    <a class="button button-small"  href="">Segsaef<br>dehssshs</a> 
    <a class="button"               href="">Segsaef<br>dehssshs</a> 
    <a class="button button-large"  href="">Segsaef<br>dehssshs</a> 
    ';
    */
    if ($params['dont-output-multirec-buttons'])
        return;
    Blox::includeTerms($terms);
    $scripts = $terms['scripts'];
    
    foreach ($scripts as $i=>$s) {
        $clas = '';
        $query = '';
        if ($i==0) { # edit multi-rec
            if (isset($_GET[$s[0]]))
                if ($params['multi-record'] && !$_GET['rec'] && !$xprefix)
                    $clas.= ' active';
        } elseif ($i==1) { # edit xdata
            $query .= '&xprefix=x&rec=1';
            if (isset($_GET[$s[0]])) {
                if (!$xdatExists)
                    $clas.= ' disabled';
                elseif ($xprefix)
                    $clas.= ' active';
            }
        } elseif ($i==2) { # sort-manualy
            if (isset($_GET['edit']) && $_GET['rec']) {
                $clas.= ' disabled';
            } elseif (isset($_GET[$s[0]])) {
                $clas.= ' active';
            }
        } elseif ($i==3) { # recs-select
            if (isset($_GET['edit']) && $_GET['rec']) {
                $clas.= ' disabled';
            } elseif (isset($_GET[$s[0]])) {
                $clas.= ' active';
            }
        }
        $href = '?'.$s[0].'&block='.$blockInfo['id'].$query.$pagehrefQuery;
        echo'<a class="button'.$clas.'" href="'.$href.'" title="'.$s[2].'">'.$s[1].'</a> ';
    }
    