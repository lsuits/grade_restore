<?php


/*  Two capabilities required for the simplied restore block; one to configure the block
    and the other to actually use it, instead of using the built restore capability.
    Simplified restore represents a "dumbed" down version of the regular restore, so
    the system can run with restore disabled, and simple restore enabled.
*/


$block_grade_restore_capabilities = array (
                'block/grade_restore:canrestore' => array(
                    'captype'=>'write',
                    'contextlevel' => CONTEXT_BLOCK,
                    'legacy'=> array (
                        'admin' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW
                    )
                 ),      

                'block/grade_restore:cansearch' => array(
                    'captype' => 'read',
                    'contextlevel' => CONTEXT_BLOCK,
                    'legacy' => array(
                        'admin' => CAP_ALLOW,
                        'coursecreator' => CAP_ALLOW
                    )
                )
    );

?>
