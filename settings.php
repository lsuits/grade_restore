<?php  //$Id: settings.php,v 1.0 2008-05-06 17:38:47 pcali1 Exp $

//This is taking the great assumption that the admin will only have ONE pinned block
//in the courses page | v2.0 of simple restore should have it's own table
$link ='<a href="'.$CFG->wwwroot.'/blocks/backadel/config.php">'.
            get_string('backadel_settings', 'block_backadel').'</a>';
$settings->add(new admin_setting_heading('block_grade_restore_config', '', $link));


?>
