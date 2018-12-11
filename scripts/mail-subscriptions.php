<?php

#NOTTESTED in v.13

/**
 * Mailing of subscribed blocks
 */

if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
    Blox::execute('?error-document&code=403');

if (Sql::tableExists(Blox::info('db','prefix').'subscriptions'))
{
    # Is the old mailing completed 
    if (Sql::tableExists(Blox::info('db','prefix').'newslettersrecipients')) {
        $sql = "SELECT `user-id` FROM ".Blox::info('db','prefix')."newslettersrecipients LIMIT 1";
        if ($result = Sql::query($sql)) {
            $num = $result->num_rows;
            $result->free();
            if ($num > 0)
                $scriptResult = 'OldNewslettersAreNotSent';
        }
    }
    # New mailing
    if ($scriptResult != 'OldNewslettersAreNotSent') {
        $newsletters_sums = [];
        # The total number of subscribers
        $props = Proposition::get('user-is-subscriber', 'all', 'any');
        $newsletters_sums['subscribers'] = count($props);

        # For each subscription
        $sql = "SELECT * FROM ".Blox::info('db','prefix')."subscriptions";
        if ($result = Sql::query($sql)) {
            $subscriptions = [];
            while ($row = $result->fetch_assoc()) { # `block-id` `last-mailed-rec` `activated`
                # How many new records
                $blockInfo = Blox::getBlockInfo($row['block-id']);
                $tbl = "`".Blox::getTbl($blockInfo['tpl'])."`";
                $sql = "SELECT COUNT(*) FROM $tbl WHERE `rec-id` >? AND `block-id`=?";
                if ($result2 = Sql::query($sql, [$row['last-mailed-rec'], $row['block-id']])) {
                    $newsletters_sums['new-recs'] += $row['num-of-new-recs'] = $result2->fetch_row()[0]; # NOTTESTED    //mysqli_data_seek($result2, 0);
                    $result2->free();
                }
                # The number of subscribers
                $props = Proposition::get('user-is-subscriber', 'all', $row['block-id']);
                $row['num-of-subscribers'] = count($props);
                # page-title
                $row['page-id'] = Blox::getBlockPageId($row['block-id']);
                $pageInfo = Router::getPageInfoById($row['page-id']);
                $row['page-title'] = $pageInfo['title'];
                #
                $subscriptions[] = $row;
            }
            $result->free();
        }
        Store::set('newsletters_sums', $newsletters_sums);
        $template->assign('newsletterParams', Store::get('newsletter-params'));
        $template->assign('subscriptions', $subscriptions);
        $template->assign('newsletters_sums', $newsletters_sums);# For a report
        if (empty($newsletters_sums['new-recs']) || empty($newsletters_sums['subscribers']))
            $scriptResult = 'nothingToSend';
    }
} else {
    $scriptResult = 'noSubscriptions';
}

$template->assign('scriptResult', $scriptResult);
include Blox::info('cms','dir')."/includes/button-cancel.php";
include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";