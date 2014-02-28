<?php // $Id: restore.php,v 1.0 2008/04/06 10:30:43 pcali1 Exp $


require_once('../../config.php');
require_once("../../backup/restorelib.php");
require_once("../../lib/xmlize.php");
require_once("../../course/lib.php");
require_once("../../backup/lib.php");
require_once('lib.php');
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/adminlib.php");

@ini_set("max_execution_time", "300000");
@ini_set("memory_limit", "1280M");

$file          = required_param('file', PARAM_TEXT);
$action        = optional_param('action', '', PARAM_ACTION);
$backup_data   = optional_param('backup_unique_code', '', PARAM_TEXT);
$dir           = optional_param('dir', '', PARAM_TEXT);
$restore_to    = required_param('restore_to', PARAM_INT);

require_login();

$blockname = get_string('pluginname', 'block_grade_restore');
$navigation = array(
            array('name' => 'Backups', 'link' => "{$CFG->wwwroot}/blocks/grade_restore/list.php?restore_to=$restore_to", 'type'=>'title'),
            array('name' => $blockname, 'link' => '', 'type'=> 'title'),
            );

print_header_simple(get_string('grade_restore_restore', 'block_grade_restore'), 
        '', build_navigation($navigation));

//handle actions in here
switch ($action) {
    case "restore":
        //we got the ok, time to restore
        $errorstr = '';
        
        //build the restore object from our settings and $SESSION info
        $restore = restore_object(0, $file, $backup_data, $restore_to);

        //print_r($restore) and die;
        $course_header = $SESSION->course_header;
        $course_header->course_shortname .= '_'. $USER->username;
        $course_header->course_idnumber .= '_'. $USER->username;
        restore_execute($restore, $SESSION->info, $course_header, $errorstr);
        if ($errorstr) {
            error($errorstr);
        }

        print_continue("$CFG->wwwroot/course/view.php?id={$restore->course_id}");
        break;

    default:
        //they just came here... put up disclaimer (and put some stuff in the SESSION)
        define('RESTORE_SILENTLY', true);
        $errorstr = '';
        //With RESTORE_SILENTLY set, this function will place everything needed in the 
        //SESSSION without first displaying anything about the course
        $backup_data = restore_precheck(0, $dir.'/'. $file, $errorstr, true);

        if ($errorstr) {
            error($errorstr);
        } else if ($restore_to == 0){
            //It's set to delete the current course, Throw warning;  If yes, 
            //send the data back to itself to begin restoring
            notice_yesno(get_string('warning', 'block_grade_restore'), 
                "$CFG->wwwroot/blocks/grade_restore/restore.php?file=$file".
                "&amp;backup_unique_code=$backup_data&amp;action=restore&amp;restore_to=$restore_to", 
                "$CFG->wwwroot/blocks/grade_restore/list.php?restore_to=$restore_to");
        } else {
            //Adding to the current course; no warning needed
            redirect("$CFG->wwwroot/blocks/grade_restore/restore.php?file=$file".
                 "&amp;backup_unique_code=$backup_data&amp;action=restore&amp;restore_to=$restore_to", 
                 get_string('waiting', 'block_grade_restore'));
        }
}

print_footer();

?>
