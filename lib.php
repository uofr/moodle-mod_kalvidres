<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module newmodule
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the newmodule specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod_kalvidres
 * @copyright (C) 2016-2017 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $kalvidres An object from the form in mod_form.php
 * @return int The id of the newly inserted kalvidassign record
 */
function kalvidres_add_instance($kalvidres) {
    global $DB;

    $kalvidres->timecreated = time();

    $kalvidres->id =  $DB->insert_record('kalvidres', $kalvidres);

    return $kalvidres->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $kalvidres An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function kalvidres_update_instance($kalvidres) {
    global $DB;

    $kalvidres->timemodified = time();
    $kalvidres->id = $kalvidres->instance;

    $updated = $DB->update_record('kalvidres', $kalvidres);

    return $updated;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id - Id of the module instance
 * @return boolean - Success/Failure
 */
function kalvidres_delete_instance($id) {
    global $DB;

    if (! $kalvidres = $DB->get_record('kalvidres', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('kalvidres', array('id' => $kalvidres->id));

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course - Moodle course object.
 * @param object $user - Moodle user object.
 * @param object $mod - Moodle moduble object.
 * @param object $kalvidres - An object from the form in mod_form.php.
 * @return object - outline of user.
 * @todo Finish documenting this function
 */
function kalvidres_user_outline($course, $user, $mod, $kalvidres) {
    $return = new stdClass;
    $return->time = 0;
    $return->info = ''; // TODO finish this function.
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * @param object $course - Moodle course object.
 * @param object $user - Moodle user object.
 * @param object $mod - Moodle module obuject.
 * @param object $kalvidres - An object from the form in mod_form.php.
 * @return boolean - this function always return true.
 * @todo Finish documenting this function
 */
function kalvidres_user_complete($course, $user, $mod, $kalvidres) {
    return true;  // TODO: finish this function.
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in kalvidres activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param object $course - Moodle course object.
 * @param array $viewfullnames - fullnames of course.
 * @param int $timestart - timestamp.
 * @return boolean - True if anything was printed, otherwise false.
 * @todo Finish documenting this function
 */
function kalvidres_print_recent_activity($course, $viewfullnames, $timestart) {
    // TODO: finish this function
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @return bool - this function always return false.
 */
function kalvidres_cron () {
    return false;
}

/**
 * Must return an array of users who are participants for a given instance
 * of kalvidres. Must include every user involved in the instance, independient
 * of his role (student, teacher, admin...). The returned objects must contain
 * at least id property. See other modules as example.
 *
 * @param int $kalvidresid - ID of an instance of this module
 * @return mixed - false if no participants, array of objects otherwise
 */
function kalvidres_get_participants($kalvidresid) {
    // TODO: finish this function
    return false;
}

/**
 * This function return support status.
 * @param string $feature - FEATURE_xx constant for requested feature
 * @return mixed - True if module supports feature, null if doesn't know
 */
function kalvidres_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:        
						return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

function kalvidres_cm_info_dynamic(cm_info $cm) {
	
    global $CFG, $DB, $OUTPUT, $USER;
    
    $mediaid = $cm->instance;
	
	$media_entry = $DB->get_record('kalvidres', array('id' => $mediaid));
   	
	$out = '';
	/*
	if (!empty($media_entry->intro)) {
		$attr = array('class'=>'no-overflow');
		$out .= html_writer::start_tag('div',$attr);
		$out .= format_module_intro('kalvidres', $media_entry, $cm->id, false);
		$out .= html_writer::end_tag('div');
	}
	*/
    //$out = '{'.print_r($entry,1).'}';
    
    //if (!empty($this->current->entry_id)) {


        // Check if the entry object is cached
        $entry_obj  = local_kaltura_get_ready_entry_object($media_entry->entry_id);

//$out = '{'.print_r($entry_obj,1).'}';



        if (isset($entry_obj->thumbnailUrl) && $media_entry->showpreview == 1) {
            $source = $entry_obj->thumbnailUrl;
            $alt    = $entry_obj->name;
            $title  = $entry_obj->name;
			
						if ($media_entry->height) {
							$height = $media_entry->height;
						}
						if ($media_entry->width) {
							$width = $media_entry->width;
						}


					    $attr = array('src' => $source,
					                  'alt' => $alt,
					                  'title' => $title,
					            	  'width'  => '100%'
					                  );
											/*			
									  if (isset($height)) {
										  $attr['height'] = $height;
									  }
				  
									  if (isset($height)) {
										  $attr['width'] = $width;
									  }*/
						
										$link_url = $cm->url;
										if ($media_entry->showpreview == 1) {
											$link_url .= '&autoPlay='.$media_entry->showpreview;
											$cm->set_on_click('location.href = \''.$cm->url . '&autoPlay='.$media_entry->showpreview.'\'; return false;');
											
											//$cm->url = $cm->url . '&autoPlay=1';
										}
										
						$linkattr = array(
						  'href' => $link_url,
						);
				    
						$divattr = array('class' => 'kalvidres_thumbnail');
						
						$out .= html_writer::start_tag('div',$divattr);
						$out .= html_writer::start_tag('a', $linkattr);
						//span for the play arrow
					  $out .= html_writer::start_tag('span');
					  $out .= html_writer::end_tag('span');
						
					  $out .= html_writer::empty_tag('img', $attr);
						//$out .= html_writer::end_tag('a');
						
						$title_attr = array();
						
						$out .= html_writer::empty_tag('br');
						
						
						$out .= html_writer::start_tag('h4');
						//$out .= html_writer::start_tag('a', $linkattr);
						$out .=	$media_entry->name;
						$out .= html_writer::end_tag('h4');
						$out .= html_writer::end_tag('a');
						
						if ($cm->showdescription) $out .= $media_entry->intro;
						//$out .= html_writer::end_tag('div');
						
						$out .= html_writer::end_tag('div');
						
						//$out .= '<hr />'.$cm->showdescription;
						
						//$out .= '<pre>'.print_r($media_entry,1).'</pre>';
	
	        }

		//}
	
    
	
	//$out .= '|'.print_r($mediaid,1).'|';   
    
    if (!empty($out)) {
			// if we're showing the preview, hide the activity icon and description
			if ($media_entry->showpreview == 1) $cm->set_no_view_link();
    	$cm->set_content($out);
    } // UofR Hack
    
}

/**
 * Add a get_coursemodule_info function in case any assignment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function kalvidres_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
	
	/*
    $dbparams = array('id'=>$coursemodule->instance);
    $fields = 'id, name, alwaysshowdescription, allowsubmissionsfromdate, intro, introformat';
    if (! $assignment = $DB->get_record('assign', $dbparams, $fields)) {
        return false;
    }
	*/
	
	if (! $kalvidres = $DB->get_record('kalvidres', array('id' => $coursemodule->instance))) {
        return false;
    };
	
  $result = new cached_cm_info();
    $result->name = $kalvidres->name;
		//$result->content = 'hello';
		//die(print_r($coursemodule,1));
    if ($coursemodule->showdescription) {
        //if ($kalvidres->alwaysshowdescription) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('kalvidres', $kalvidres, $coursemodule->id, false);
        //}
    }
    return $result;
}