<?php // $Id: list.php,v 1.0 2008/04/01 09:53:43 pcali1 Exp $

/**
 *  Author: Philip Cali
 *  Date: 4/01/08
 *  Louisiana State University
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$restore_to = required_param('restore_to');

$context = get_context_instance(CONTEXT_SYSTEM);

// Get all the user's courses that they teach
if (has_capability("block/grade_restore:cansearch", $context)) {
    if (empty($SESSION->grade_restore_searchsql)) {
        redirect('search.php?id=' . $id . '&amp;restore_to='. $restore_to);
    }
    $sql = $SESSION->grade_restore_searchsql;
    $col = $SESSION->grade_restore_collection;
    $courses = get_records_sql($sql);
    unset($SESSION->grade_restore_searchsql);
    unset($SESSION->grade_restore_collection);
} else {
    $courses = get_user_courses_bycap($USER->id, 'moodle/course:update', get_user_access_sitewide($USER->id),
                                  false, 'c.sortorder ASC', array('id', 'sortorder', 'category', 'shortname', 'idnumber'));
}

// get the js required for "folder" operations
require_js(array('yui_dom-event', 'yui_animation',
        $CFG->wwwroot . '/blocks/grade_restore/lib/selection.js'));

$blockname = get_string('pluginname', 'block_grade_restore');
$navigation = array (
            array('name' => $blockname, 'link' => '', 'type'=>'title'),
            );

print_header_simple(get_string('grade_restore_list', 'block_grade_restore'), 
        '', build_navigation($navigation));

//Integrate with backup and delete
if (get_field('block', 'id', 'name', 'backadel')) {
    $backup_dir = get_config('block/backadel', 'path');  
    $search_pattern = get_config('block/backadel', 'pattern');
} 

if (!$backup_dir || !$search_pattern) {
    error("Please configure Backdel first");
}

if (isset($col)) {
    $search_pattern = $col['shortname'];
}


//grab the backed up courses
if (strlen($backup_dir) > 0) {
    //Look in base directory
    build_table_from_directory($CFG->dataroot . '/' . $backup_dir, $search_pattern,  get_string('root_location', 
                       'block_grade_restore'), false, ($browse == '1') ?true : false, true);
}

echo ("<div id=\"sr_flash_message\" class=\"flash_message\"><p>". 
        get_string('please_wait', 'block_grade_restore').
    " <img src=\"{$CFG->wwwroot}/blocks/grade_restore/loading.gif\" alt=\"loading...\" /> </p></div>");

print_footer();

// Builds a single table from each course, or the main backup directory
function build_table_from_directory($dir, $search_pattern, $table_title, $use_zips=false, $use_cats=false, $recursive=true) {
      global $CFG, $USER;
      
      /*if (is_siteadmin($USER->id)) {
            $use_zips = true;
      }*/ 

      if (!is_dir($dir)) {
        return;
      }

      $directory = scandir($dir);
      $backups = array_filter($directory, "filter_backups");
 
      if (empty($backups) and !$recursive) {
          return;
      }

      print_table_title($table_title);
      display_table();

      $row = 1;

      //Has been modified since it was last recursive, now it only looks one deep
      if($recursive) {
           foreach ($directory as $item) {
            if (!preg_match('/\./', $item)) {
                $output_str = $inner_rows = "";

                $filedate = userdate(filemtime($dir."/". $item), "%d %b %Y, %I:%M %p");
                $row = ($row == 0) ? 1: 0;

                $row_id = "sr_row_{$item}";
                $icon = "<img src=\"$CFG->pixpath/f/folder.gif\" class=\"icon\" alt=\"folder\" />";
                        
                $output_str  = "<tr class=\"sr_row{$row}\">";
                $output_str .= cell_string($icon . "<a onclick=\"toggleInnerRows('{$row_id}')\" href=\"javascript:void(0);\">{$item}</a>", 'name');
                $output_str .= cell_string('--', 'size');
                $output_str .= cell_string($filedate, 'date');
                $output_str .= "</tr>";

                $sub_directory = array_filter(scandir($dir . '/' . $item), "filter_backups");
                foreach ($sub_directory as $file) {
                      $inner_rows .= print_row_from_backups($dir. '/'. $item, $file, $search_pattern, $use_zips,
                                    'sr_inner_row'. $row, $row_id, 'display: none;', $use_cats);        
                }
                        
                //If the inner_rows don't exist, then don't even bother with printing out the the parent row
                if ($inner_rows != "") {
                   echo "{$output_str}";
                   echo "{$inner_rows}";
                }
            }
         }
     }

     foreach ($backups as $file) {
          $row = ($row == 0) ? 1: 0;
          echo (print_row_from_backups($dir, $file, $search_pattern, $use_zips, 'sr_row'. $row));
     }
     close_table();
}

function print_row_from_backups($dir, $file, $search_pattern, $use_zips=false, $row_class='sr_row', $row_name='sr_row_name', $style='display: table-row;', $use_cats=false) {
    global $CFG;
    global $id, $cat_roles, $restore_to;
    
    $return_str = "";
        if ($use_zips || preg_match('/_'. obtain_pattern($search_pattern) .'_/', $file) || 
            ($use_cats && category_in_file($cat_roles, $file))) {

           $wdir = substr($dir, strlen($CFG->dataroot)+1);
           $filedate = userdate(filemtime($dir . "/". $file), "%d %b %Y, %I:%M %p");
           
           $icon = "<img src=\"$CFG->pixpath/f/zip.gif\" class=\"icon\" alt=\"zip\" />";
           $link = 'href="'.$CFG->wwwroot.'/blocks/grade_restore/restore.php?id='.$id.'&amp;file='.
                    urlencode($file).'&amp;dir='.urlencode($wdir).'&amp;restore_to='.$restore_to.'">'. $file .'</a>';

           //print out the file as a table row
           $return_str .= "<tr id=\"{$row_name}\" name=\"{$row_name}\" style=\"{$style}\" class=\"{$row_class}\">";
           $return_str .=  cell_string($icon . "<a onclick=\"processingRestore()\" {$link}", 'name');
           if(filesize($dir . '/' . $file) == '') {
               $largefile = $dir . '/' . $file;
               $largesize = number_format((trim(`stat -c%s $largefile`)/1024/1024/1024), 1) . 'GB'; 
               $return_str .=  cell_string($largesize, 'size');
           } else {
               $return_str .=  cell_string(display_size(filesize($dir . '/' . $file)), 'size');
           }
           $return_str .=  cell_string($filedate, 'date');
           $return_str .= "</tr>";
       }

    return $return_str;
}

/**
 * We want to filter the backup by .zip, what moodle expects
 */
function filter_backups($backup) {
    return preg_match('/\.zip$/', $backup);
}

/*
    A simple function returning the actual string to match in the in backup file name
*/
function obtain_pattern($pattern) {
    global $USER;
    switch($pattern) {
        case 'idnumber':
            return $USER->idnumber;
        case 'fullname':
            return strtolower($USER->firstname).'_'. strtolower($USER->lastname);
        case 'username':
            return $USER->username;
        default:
            return $pattern;
    }
}

function category_in_file($cat_roles, $file) {
    if (!$cat_roles && empty($cat_roles)) {
        return false;
    }

    foreach ($cat_roles as $cat_name) {
        if (preg_match('/' . $cat_name . '/' , $file)) {
            return true;
        }
    }

    return false;
}

/*
    Prints out the h2 header before the actual table is rendered
 */
function print_table_title($title) {
    echo '<h2 id="tableName" name="tableName" class="sr_table_identifier">'. $title .'</h2>';
}

/**
 * Returns a string representing a cell in a table row
 */
function cell_string($text, $class) {
    return '<td class="sr_table_cell '.$class.'">'. $text .'</td>';
}

/**
 * Prints out the table with th elements
 */
 function display_table () {
    $strname = get_string("name");
    $strsize = get_string("size");
    $strmodified = get_string("modified");

    echo "<table name=\"sr_table_name\" class=\"sr_backup_files\">";
    echo "<tr>";
    echo "<th class=\"header name\" scope=\"col\">$strname</th>";
    echo "<th class=\"header size\" scope=\"col\">$strsize</th>";
    echo "<th class=\"header date\" scope=\"col\">$strmodified</th>";
    echo "</tr>\n";

 }

/**
 * Simply closes the last table opened
 */
 function close_table (){
    echo "</table>";
 }

?>
