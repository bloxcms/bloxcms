<?php
/**
 * NOT USED. NOT DEBUGGED
 */
?>
    
<script type='text/javascript'>
    function disableFileUpload()
    {
        document.getElementById('backupFile').disabled = 'disabled';
    }
    function enableFileUpload()
    {
        document.getElementById('backupFile').disabled = null;
    }
</script>

<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo"
<div class='blox-edit'>
<div class='heading'>{$terms['bar-title']}</div>
";
if ('before-backup'==$action)
{
    echo"
        <br />
        <form action='?dump&action=backup".$pagehrefQuery."' method='post'>
        <table>
    ";
    if ($backupFilesInfo)
    {
        echo"<tr><th colspan='4'>'.$terms['prev-dumps'].'</th></tr>";
        foreach ($backupFilesInfo as $i => $fInfo)
        echo"
            <tr>
            <td style='vertical-align:bottom'><a href='".Blox::info('site','url')."/temp/{$fInfo['name']}' title='{$terms['download']}'>{$fInfo['name']}</a></td>
            <td class='small' style='vertical-align:bottom'>{$fInfo['time']}</td>
            <td class='small' style='vertical-align:bottom' align='right'><b>{$fInfo['size']}</b> KB</td>
            <td class='small gray' style='vertical-align:bottom'><input type='checkbox'  name='filesToDel[{$i}]' value='{$fInfo['name']}' id='dump1{$i}' /><label for='dump1{$i}'>{$terms['delete']}</label></td>
            </tr>
        ";
    }

    echo"
            <tr><th colspan='4'><br />{$terms['create-new']}</th></tr>
            <tr><td colspan='4'>
                <table width='100%' class='center'>
                <tr>
                    <td width='33%'><input name='compress-method' value='sql' type='radio' id='dump1' /><label for='dump1'>{$terms['no-compression']}</label></td>
                    <td width='33%'><input name='compress-method' value='gz' type='radio' checked id='dump2' /><label for='dump2'>gzip</label></td>
                    <td width='33%'><input name='compress-method' value='bz2' type='radio' id='dump3' /><label for='dump3'>bzip2</label></td>
                </tr>
                </table>

            </td></tr>

            <tr><td colspan='4'>{$submitButtons}</td></tr>
        </table>
        </form>
    ";
}
elseif ('backup'==$action)
echo"
    {$report}<br />
    {$terms['download']}: <a href='".Blox::info('site','url')."/temp/{$backupFileName}'><b>{$backupFileName}</b></a> <span class='small'>{$fsize}</span>
    <br /><br />
    {$cancelButton}
";
elseif ('before-restore'==$action)
{
    echo"

        <form action='?dump&action=restore".$pagehrefQuery."' method='post' enctype='multipart/form-data'>
            <table>


            <tr>
            <td colspan='4'>
                <input name='backup-file-name' value='upload' type='radio'  onClick='enableFileUpload()'; />
                <input name='upload-file' id='backupFile' type='file' />
            </td>
            <td class='small gray' style='border-left:solid 1px #999'>{$terms['from-local']}<td>
            </tr>


            <tr><td colspan='5' height='5'> </tr>

            <tr><td></td><td></td><td></td><td></td><td class='small gray' style='border-left:solid 1px #999' rowspan='99'>{$terms['from-server']; if ($backupFilesInfo[2]) echo'.<br />'.$terms['sorted'];}</td></tr>
    ";


            if ($backupFilesInfo)
            {
                foreach ($backupFilesInfo as $fInfo)
                {
                    echo"
                        <tr>
                        <td><input name='backup-file-name' value='{$fInfo['name']}' type='radio' checked onClick='disableFileUpload()'; /></td>
                        <td><a href='".Blox::info('site','url')."/temp/{$fInfo['name']}' title='{$terms['download']}'>{$fInfo['name']}</a></td>
                        <td class='small'>{$fInfo['time']}</td>
                        <td class='small'>{$fInfo['size']} KB</td>
                        </tr>
                   ";
                }
            }


    echo"
            </table>
            <table>
            <tr><td colspan='2' class='small'><br /><span class='red'>{$terms['warning']}</span><br />{$terms['advise']}</td></tr>
            <tr><td colspan='2'>{$submitButtons}</td></tr>
            </table>
        </form>
    ";
}
elseif ('restore'==$action)
echo"
    {$report}<br /><br />
    {$cancelButton}

";
echo"
</div>
";
?>