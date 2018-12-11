<?php 
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['heading'].Admin::tooltip('installation-of-system.htm').'</div>
    <table>
    <tr>
    <td>';
        if (Blox::info('user','user-is-admin')) {
            if ($tables-deleted) 
                echo $terms['tables-deleted'];
            elseif ($filesDeleted) 
                echo $terms['files-deleted'];

            if ($tablesCreated)
                echo'<br />'.$terms['tables-created'];

        	if ($outputTree) {
                echo'<p>'.$terms['files-detected'].'</p><br>';
                showTree($outputTree, $terms);
                echo'
                <form action="?install&del-files" method="post">
                    <div class="alert orange">'.$terms['del-files'].'</div>
                    '.$submitButtons.'
                </form>';
            } elseif ($tableNames) {
                echo'<p>'.$terms['tables-detected'].'</p><br>';
        		outputTables($tableNames, $terms);
                echo'
                <form action="?install&del-tables" method="post">
                    <div class="alert orange">'.$terms['del-tables'].'</div>
                    '.$submitButtons.'
                </form>';
            } else
        		logoutForm($submitButtons, $notCreatedDirs, $notPubDirs, $terms);
        } 
        else { # if visitor
        	if ($outputTree) { # files-detected
                echo'
                <p>'.$terms['files-detected'].'</p><br>
                <form action="?install" method="post">
                    <div>'.$terms['del-files-viz'].'</div>
                    '.$submitButtons.'
                </form>';
            } elseif ($tableNames) {  # tables-detected
                echo'
                <p>'.$terms['tables-detected'].'</p><br>
                <form action="?install" method="post">
                    <div>'.$terms['del-tables-viz'].'</div>
                    '.$submitButtons.'
                </form>';
            } else {
                echo $terms['tables-created'];
        		logoutForm($submitButtons, $notCreatedDirs, $notPubDirs, $terms);
            }
        }
        echo'
    </td>
    </tr>
    </table>
</div>';
    
    




    function logoutForm($submitButtons, $notCreatedDirs, $notPubDirs, $terms)
    {
    	echo'<br />'.$terms['cms-is-installed'];
        if ($notCreatedDirs) {
            echo'<br />'.$terms['not-created-dirs'];
        	outputDirs($notCreatedDirs);
        }
        if ($notPubDirs) {
            echo'<br />'.$terms['not-pub-dirs'];
        	outputDirs($notPubDirs);
        }
        echo'
        <form action="?logout" method="post">
            <input type="hidden" name="login" value="1" />
            '.$terms['log-in'].'
            '.$submitButtons.'
        </form>';
    }



    function outputDirs($dirs)
	{
        echo'
        <table class="bevel small">
        <tr>
        <td style="padding:9px; border:0px">
            <ul style="list-style-type:disc; margin: 0px 0px 0px 20px; padding:0px">';
                foreach ($dirs as $d)
                    echo'<li>'.$d.'</li>';
                echo'
            </ul>
        </td>
        </tr>
        </table><br />';
    }






    function outputTables($tableNames)
    {
        echo'
        <table class="bevel small">
        <tr>
        <td style="padding:9px; border:0px">
            <ul style="list-style-type:disc; margin: 0px 0px 0px 20px; padding:0px">';
                foreach ($tableNames as $tableName)
                    echo'<li>'.$tableName.'</li>';
                echo'
            </ul>
        </td>
        </tr>
        </table><br />';
    }


    function showTree($outputTree)
    {
        echo'
        <table class="bevel small">
        <tr>
        <td style="padding:9px; border:0px">
            '.$outputTree.'
        </td>
        </tr>
        </table><br />';
    }