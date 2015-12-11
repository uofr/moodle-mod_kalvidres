<?php

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
 * Kaltura video resource renderer class
 *
 * @package    mod
 * @subpackage kalvidres
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

class mod_kalvidres_renderer extends plugin_renderer_base {

    function display_mod_info($title) {

        $output = '';

        //$attr = array('for' => 'video_name');
        //$output .= html_writer::tag('label', get_string('vid_prop_name', 'kalvidres'), $attr);
        $attr = array('class'=>'kalvidres_video_title');
        $output .= html_writer::tag('div', $title, $attr);
        //$output .= '&nbsp;';

        //$output .= html_writer::start_tag('b');
        //$output .= html_writer::tag('div', $title);
        //$output .= html_writer::end_tag('b');
        //$output .= html_writer::empty_tag('br');


        return $output;
    }
    
    function embed_video($kalvidres) {
        global $PAGE;

        $output = '';
        $entry_obj = local_kaltura_get_ready_entry_object($kalvidres->entry_id);

        if (!empty($entry_obj)) {
            
            // Check if player selection is globally overridden
            if (local_kaltura_get_player_override()) {
                $new_player = local_kaltura_get_player_uiconf('player_resource');
                $kalvidres->uiconf_id = $new_player;
            }

            $courseid = get_courseid_from_context($PAGE->context);

            // Set the session
            $session = local_kaltura_generate_kaltura_session(array($entry_obj->id));

            $entry_obj->width = $kalvidres->width;
            $entry_obj->height = $kalvidres->height;

            // Determine if the mobile theme is being used
            $theme = get_selected_theme_for_device_type();

            if ($entry_obj->mediaType ==2) {
                
                $markup = html_writer::tag('img', '', array('src'=>'https://kaltura.cc.uregina.ca/p/106/sp/10600/raw/entry_id/'.$entry_obj->id.'/version/100000'));
                
            } else if ($entry_obj->mediaType == 5) {
                // mediaType 5 = audio?
                //die(print_r($entry_obj,1));
                
                //$audio_link = html_writer::tag('a','Link text',array('href'=>'https://kaltura.cc.uregina.ca/p/106/sp/10600/playManifest/entryId/'.$entry_obj->id.'/format/url/flavorParamId/0/audio.mp3'));
                //$mediarenderer = $PAGE->get_renderer('core', 'media');
                //$markup = $mediarenderer->embed_url(new moodle_url('https://kaltura.cc.uregina.ca/p/106/sp/10600/playManifest/entryId/'.$entry_obj->id.'/format/url/flavorParamId/0/audio.mp3'),null,null,null,array(core_media::OPTION_BLOCK=>1));
                
                $markup = local_kaltura_get_kdp_code($entry_obj, KALTURA_PLAYER_PLAYERAUDIO, $courseid, $session);
                    
            
            } else {
                if (0 == strcmp($theme, 'mymobile') || 0 == strcmp($theme, 'urmobile')) {
                    $markup = local_kaltura_get_kwidget_code($entry_obj, $kalvidres->uiconf_id, $courseid, $session);
                } else {
                    $markup = local_kaltura_get_kdp_code($entry_obj, $kalvidres->uiconf_id, $courseid, $session);
                }
            }
            
            //$output .= html_writer::start_tag('center');
            $output .= html_writer::tag('div', $markup, array('style'=>'text-align: center','class'=>'flex-video'));
            //$output .= html_writer::end_tag('center');
        } else {
            $output = get_string('video_converting', 'kalvidres');
        }

        return $output;
    }

    function connection_failure() {
        return html_writer::tag('p', get_string('conn_failed_alt', 'local_kaltura'));
    }


}