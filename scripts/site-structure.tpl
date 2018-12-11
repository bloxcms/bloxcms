<?php Blox::addToHead(Blox::info('cms','url').'/assets/blox.treeMenu.js') ?>
<div class="blox-edit">
    <div class="heading"><?php echo $terms['heading'] ?></div>
    <style>

    </style>
    <table><tr><td>
        <div class="treemenu"><?php echo $listOfPages ?></div>
        <script type="text/javascript">initMenus()</script>
        <br />
        <?php echo $cancelButton ?>
    </td></tr></table>
</div>