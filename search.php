<?php // $Id: search.php, v1.0 2009/01/07 08:10:34 pcali1   Exp $

/**
 * Author: Philip Cali
 * Date: 1/07/09
 * Louisiana State University
 */

require_once('../../config.php');
require_once('lib.php');

$restore_to = required_param('restore_to', PARAM_INT);

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

$criterion = array('shortname' => get_string('shortname'));

//This user does not have the correct permissions
if (!has_capability("block/grade_restore:canrestore", $context) ||
    !has_capability("block/grade_restore:cansearch", $context)) {
     error('err_permission', 'block_grade_restore');
}

$blockname = get_string('blockname', 'block_grade_restore');
$navigation = array(
    array('name' => $blockname, 'link' => '', 'type'=>'title'),
);

if ($data = data_submitted()) {
    $SESSION->grade_restore_searchsql = get_sql($data, 'course', 
            'id, shortname, fullname, category, idnumber', 
            $criterion);
   
    $SESSION->grade_restore_collection = translate_filesearch($data, $criterion); 
    redirect('list.php?id='.$id.'&amp;restore_to='.$restore_to);
}

print_header_simple('', '', build_navigation($navigation));
print_heading(get_string('filter_courses', 'block_grade_restore'));

echo '<form name="search_form" action="search.php" method="POST">
      '.get_search_html($criterion).'
      <input type="hidden" name="id" value="'.$id.'"/>
      <input type="hidden" name="restore_to" value="'.$restore_to.'"/>
      <input type="submit" value="'.get_string('submit').'"/>
      </form>';

print_footer();
?>
