<?php // $Id: block_grade_restore.php,v 1.0 2008/04/01 10:30:43 pcali1 Exp $


    /**
    *   Grades Restore Block
    *
    */

class block_grade_restore extends block_list {
 
    function init() {
        $this->title = get_string('pluginname', 'block_grade_restore');
    }

    /**
     * Limits where the block can be added.
     **/
    function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    function get_content() {
        global $USER, $CFG;

        if ($this->content !==NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        
        if (empty($this->instance)) {
            return $this->content;
        }


        //Draws the links and icons
        $this->content->icons[] = "<img src=\"$CFG->pixpath/i/restore.gif\" alt=\"\"/>";
        $this->content->items[] = '<a href="'. $CFG->wwwroot . '/blocks/grade_restore/list.php?restore_to=2">' .
               get_string('restore_course', 'block_grade_restore') . '</a>';
    
        //Don't print settings unless they can configure the block
        /*if ($this->check_permissions('block/grade_restore:canconfig', $COURSE)) {
            $this->content->footer = '<a href="'. $CFG->wwwroot . '/blocks/grade_restore/config.php?instanceid='. $this->instance->id. '&amp;id='
                . $COURSE->id .'">'. get_string('restore_settings', 'block_grade_restore');    
        }*/
        return $this->content;
    }

    //Disallow config page config_instance.html
    function instance_allow_config() {
        return false;
    }

}

?>
