<?php
$pagehref = Blox::getPageHref();
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['heading'].Admin::tooltip('statistics.htm').'</div>
    <form action="?statistics-total'.$pagehrefQuery.'" method="post">
        <table>
            <tr>
            <td style="padding: 0px 0px 0px 30px">
                <b>'.$terms['subject'].'</b><br />
                <input name="statistics-subject" type="radio" value="pages"';       if ($statisticsSubject=='pages')       echo' checked'; echo' id="pages" />       <label for="pages">'.$terms['pages'].'</label><br />
                <input name="statistics-subject" type="radio" value="updates"';     if ($statisticsSubject=='updates')     echo' checked'; echo' id="updates" />     <label for="updates">'.$terms['updates'].'</label><br />
                <input name="statistics-subject" type="radio" value="downloads"';   if ($statisticsSubject=='downloads')   echo' checked'; echo' id="downloads" />   <label for="downloads">'.$terms['downloads'].'</label><br />
                <input name="statistics-subject" type="radio" value="remotehosts"'; if ($statisticsSubject=='remotehosts') echo' checked'; echo' id="remotehosts" /> <label for="remotehosts">'.$terms['remotehosts'].'</label><br />
                <input name="statistics-subject" type="radio" value="referers"';    if ($statisticsSubject=='referers')    echo' checked'; echo' id="referers" />    <label for="referers">'.$terms['referers'].'</label><br />
            </td>
            <td style="padding: 0px 0px 0px 30px">
                <b>'.$terms['time-interval'].'</b><br />
                <input name="time-interval" type="radio" value="day"';       if ($timeInterval=='day')       echo' checked'; echo' id="day" />     <label for="day">'.$terms['day'].'</label><br />
                <input name="time-interval" type="radio" value="week"';      if ($timeInterval=='week')      echo' checked'; echo' id="week" />    <label for="week">'.$terms['week'].'</label><br />
                <input name="time-interval" type="radio" value="month"';     if ($timeInterval=='month')     echo' checked'; echo' id="month" />   <label for="month">'.$terms['month'].'</label><br />
                <input name="time-interval" type="radio" value="year"';      if ($timeInterval=='year')      echo' checked'; echo' id="year" />    <label for="year">'.$terms['year'].'</label><br />
                <input name="time-interval" type="radio" value="custom"';    if ($timeInterval=='custom')    echo' checked'; echo' id="custom" />  <input name="from" value="'.$from.'" type="text" size="10" onclick="document.getElementById(\'custom\').checked=1" /> ... <input name="till" value="'.$till.'" type="text" size="10" onclick="document.getElementById(\'custom\').checked=1" />
            </td>
            </tr>';
            if ($statisticsIsOff) {
                echo'
                <tr>
                    <td colspan="2" align="center" style="color:#ff0000" class="small"><br />'.$terms['counters-are-off'].'</td>
                </tr>';
            }
            echo'
            <tr><td colspan="2">'.$submitButtons.'</td></tr>
        </table>
    </form>
</div>';