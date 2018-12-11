<?php
/**
 * Create and output text reports about the progress of the script execution.
 * @example 
 *     Report::reset(); Reset the reports at the beginning
 *     Report::add('Some message', true); true - means red color
 *     $reportHtm = Report::get(); Get the html list of reports
 */
class Report
{
    # Add one record of report
    public static function add($message, $isRed=null)
    {
        if ($message){
            $reportItem = [];
            $reportItem['message'] = $message;
            if ($isRed)
                $reportItem['is-red'] = true;
            $_SESSION['Blox']['Report'][] = $reportItem;
        }
    }

    # Get the html list of all records of report
    public static function get()
    {
        $report = '<ol>';
        foreach ($_SESSION['Blox']['Report'] as $reportItem) {
            $report .='<li';
            if ($reportItem['is-red'])
                $report .=' style="color:red"';
            $report .='>'.$reportItem['message'].'</li>';
        }
        $report .='</ol>';
        return $report;
    }

    # Remove all records of report
    public static function reset()
    {
        unset($_SESSION['Blox']['Report']);
    }
}