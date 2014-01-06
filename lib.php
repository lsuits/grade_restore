<?php

define('MODULES', 'modulescheckbox');
define('USER_DATA', 'userdatacheckbox');

function restore_object($courseid, $file, $backup_data, $restore_to) {
    global $CFG, $SESSION;
        
    $allmods = get_records("modules");

    $users = ($SESSION->info->backup_users=='course') ? 1 : 2;

    //Instead of getting this information from the user, we get it from the admin's config
    $restore = new stdClass;
    $restore->original_wwwroot = $info->original_wwwroot;
    $restore->restoreto = $restore_to;
    $restore->metacourse = ($SESSION->info->backup_metacourse == 'true') ? 1 : 0;
    $restore->users = $users;
    $restore->groups = 3;
    $restore->logs = 0;
    $restore->user_files = ($SESSION->info->backup_user_files=='true') ? 1: 0;
    $restore->course_files = ($SESSION->info->backup_course_files=='true')? 1 : 0;
    $restore->site_files = ($SESSION->info->backup_site_files=='true') ? 1 : 0;
    $restore->messages = ($SESSION->info->backup_messages=='true') ? 1 : 0;
    $restore->restore_gradebook_history = 0;
    $restore->course_startdateoffset = 0;
    $restore->course_id = $courseid;
    $restore->deleting = 0;
    $restore->file = "$CFG->dataroot/$courseid/backupdata/$file";
    $restore->backup_unique_code = $backup_data;
    $restore->backup_version = $SESSION->info->backup_backup_version;
    $restore->backup_blogs = (isset($SESSION->info->backup_blogs)) ? true : false;
    $restore->blogs = ($restore->backup_blogs)? 1 : 0;

    $restore->mods = array();
    
    //Time to build the restore object's mods
    foreach ($allmods as $mod) {
            $new_mod = new stdClass;
            $new_mod->restore = 0;
            $new_mod->userinfo = 0;
            //Check that the mod exists in the backup and admin has included it
            if (($info_mod = $SESSION->info->mods[$mod->name]) != null) {
                $new_mod->restore = 1;
                $new_mod->instances = array();
                foreach ($info_mod->instances as $instance_name => $instance) {
                    $new_instance= new stdClass;
                    $new_instance->restore = 1;
                    if ($instance->userinfo == 'true' && $users != 2) {
                        $new_instance->userinfo = 1;
                    } else {
                        $new_instance->userinfo = 0;
                    }
                    $new_mod->instances[$instance->id] = $new_instance;
                }
            }
            $restore->mods[$mod->name] = $new_mod;
    }

    $xml_file  = $CFG->dataroot."/temp/backup/".$backup_data."/moodle.xml";

    // fix for MDL-9068, front page course is just a normal course
    $allroles = get_records('role');
    $mappableroles = array();

    if ($users < 2) {
        // 1.7 and above backup
        $roles = restore_read_xml_roles($xml_file);
         if (!empty($roles->roles)) { // possible to have course with no roles
                foreach ($roles->roles as $roleid=>$role) {
                    /// first, we see if any exact role definition is found
                    /// if found, that is the only option of restoring to
                    if ($samerole = simple_same_role($allroles[$roleid], $role)) {
                        $matchrole = $samerole->id;
                        // if an exact role is found, it does not matter whether this user can assign this role or not,
                        // this will be presented as a valid option regardless
                        $mappableroles[$samerole->id] = $samerole->id;
                    } else {
                        // no exact role found, let's try to match shortname
                        // this is useful in situations where basic roles differ slightly in definition
                        // ignored
                    }
                }
            }
    }
    $restore->rolesmapping = $mappableroles;

    return $restore;
}

// May be make this better one day
function simple_same_role($role, $rolefromxml) {
    if ($role->name == $rolefromxml->name &&
        $role->shortname == $rolefromxml->shortname) {
        return $role;
    }
    return false;
}

function restore_samerole($roleid, $rolefromxml) {
    global $CFG;

    // First we try some intelligent guesses, then, if none of those work, we do a more extensive
    // search.

    // First guess, try let's use the id
    if (restore_is_samerole($roleid, $rolefromxml)) {
        return get_record('role', 'id', $roleid); 
    }

    // Second guess, try the shortname
    $testroleid = get_field('role', 'id', 'shortname', $rolefromxml->shortname);
    if ($testroleid && restore_is_samerole($testroleid, $rolefromxml)) {
        return get_record('role', 'id', $testroleid); 
    }

    // Finally, search all other roles. In orter to speed things up, we exclude the ones we have
    // already tested, and we only search roles with the same number of capabilities set in their
    // definition.
    $extracondition = '';
    if ($testroleid) {
        $extracondition = "AND roleid <> $testroleid";
    }
    $candidateroleids = get_records_sql("SELECT roleid
           FROM {$CFG->prefix}role_capabilities
           WHERE roleid <> $roleid $extracondition
           GROUP BY roleid
           HAVING COUNT(capability) = ".count($rolefromxml->capabilities));
    if (!empty($candidateroleids)) {
        foreach ($candidateroleids as $testroleid => $notused) {
            if (restore_is_samerole($testroleid, $rolefromxml)) {
                return get_record('role', 'id', $testroleid);
            }
        }
    }

    return false;
}

function restore_is_samerole($testroleid, $rolefromxml) {
    // Load the role definition from the databse.
    $rolefromdb = get_records('role_capabilities', 'roleid', $testroleid, '', 'capability,permission'); 
    if (!$rolefromdb) {
        return false;
    }

    // Quick check, do they have the permissions on the same number of capabilities?
    if (count($rolefromdb) != count($rolefromxml->capabilities)) {
        return false;
    }

    // If they do, check each one.
    foreach ($rolefromdb as $capability => $permissions) {
        if (!isset($rolefromxml->capabilities[$capability]) ||
                $permissions->permission != $rolefromxml->capabilities[$capability]->permission) {
            return false;
        }
    }
    return true;
}

function get_sql($data, $table, $fields, $criterion) {
    global $CFG;
    $sql = 'SELECT '. $fields. ' FROM '. $CFG->prefix . $table ;
    $where = array();
    foreach ($criterion as $k => $v) {
        $term = $data->{$k . '_terms'};
        $equality = $data->{$k . '_equality'};

        if (empty($term)) {
            continue;
        }

        $where[] = $k . ' '. translate_equality($equality, $term);
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' OR ', $where);
    }
    return $sql;
}

function translate_equality($equality, $term) {
    $safe_term = addslashes($term);
    switch ($equality) {
        case 'contains':
            return "LIKE '%{$safe_term}%'";
        case 'equal':
            return "= '{$safe_term}'";
        case 'starts':
            return "LIKE '{$safe_term}%'";
        case 'ends':
            return "LIKE '%{$safe_term}'";
    }
}

function translate_filesearch($data, $criterion) {
    // this return a collection to be loaded in the SESSION
    // with search terms specified by the user
    $rtn = array();
    foreach($criterion as $k => $v) {
        $term = $data->{$k . '_terms'};
        if (empty($term)) {
            continue;
        }
        $rtn[$k] = $data->{$k . '_terms'};
    }
    return $rtn;
}

function get_search_html($criterion) {
    $availability = array(
        'contains' => 'contains',
        'equal' => 'is equal to',
        'starts' => 'starts with',
        'ends' => 'ends with'
    );

    $html = '';
    foreach($criterion as $k => $v) {
        $html .= '<span class="search_label">'.$v.'</span> '.
                  get_equality_selector($k, $availability) . '
                  <input class="inputbox" type="text" name="'.$k.'_terms" id="'.$k.'"/>
                  <br/>';
    }
    return $html;
}

function get_equality_selector($name, $availability, $pre='contains') {
    return choose_from_menu($availability, $name. '_equality', $pre, '', '', '', true);
}

?>
