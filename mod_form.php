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
 * The main mod_kalvidres configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_kalvidres
 * @copyright  (C) 2016-2017 Yamaguchi University <gh-cc@mlex.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

require_once($CFG->dirroot . '/course/moodleform_mod.php');

require_login();

/**
 * class of Kaltura Media resource setting form.
 * @package mod_kalvidres
 * @copyright  (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kalvidres_mod_form extends moodleform_mod {

    /** @var default player is set. */
    protected $_default_player = false;

    /**
     * This function outputs a resource information form.
     */
    protected function definition() {
        global $CFG, $PAGE, $COURSE;

        $kaltura = new kaltura_connection();
        $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);
        $renderer = $PAGE->get_renderer('local_kaltura');

        $loginsession = '';

        if (!empty($connection)) {
            $loginsession = $connection->getKs();
        }
        
        if (strpos($PAGE->url, 'modedit.php') !== false ) {
            $PAGE->requires->css('/mod/kalvidres/css/kalvidres.css');
            $PAGE->requires->css('/local/kaltura/css/simple_selector.css');
            $PAGE->requires->js_call_amd('local_kaltura/properties', 'init');
        }
        
        /*
         * This line is needed to avoid a PHP warning when the form is submitted.
         * Because this value is set as the default for one of the formslib elements.
         */
        $uiconf_id = '';

        // Check if connection to Kaltura can be established.
        if ($connection) {
            // $PAGE->requires->js_call_amd('local_kaltura/properties', 'init', array($CFG->wwwroot . "/local/kaltura/media_properties.php"));
            $uiconf_id = local_kaltura_get_player_uiconf('player_resource');
        }

        if (local_kaltura_has_mobile_flavor_enabled() && local_kaltura_get_enable_html5()) {

            $url = new moodle_url(local_kaltura_htm5_javascript_url($uiconf_id));
            $PAGE->requires->js($url, true);
        }

        $mform =& $this->_form;

        $mform->addElement('html', $renderer->create_video_selector_modal());
        $mform->addElement('html', $renderer->create_video_properties_modal());

        /* Hidden fields */
        $attr = array('id' => 'entry_id');
        $mform->addElement('hidden', 'entry_id', '', $attr);
        $mform->setType('entry_id', PARAM_NOTAGS);

        $attr = array('id' => 'video_title');
        $mform->addElement('hidden', 'video_title', '', $attr);
        $mform->setType('video_title', PARAM_TEXT);

        $attr = array('id' => 'uiconf_id');
        $mform->addElement('hidden', 'uiconf_id', '', $attr);
        $mform->setDefault('uiconf_id', $uiconf_id);
        $mform->setType('uiconf_id', PARAM_INT);

        $attr = array('id' => 'widescreen');
        $mform->addElement('hidden', 'widescreen', '', $attr);
        $mform->setDefault('widescreen', 0);
        $mform->setType('widescreen', PARAM_INT);

        $attr = array('id' => 'height');
        $mform->addElement('hidden', 'height', '', $attr);
        $mform->setDefault('height', '365');
        $mform->setType('height', PARAM_TEXT);

        $attr = array('id' => 'width');
        $mform->addElement('hidden', 'width', '', $attr);
        $mform->setDefault('width', '400');
        $mform->setType('width', PARAM_TEXT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'kalvidres'), array('size'=>'64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'kalvidres'));

        if (local_kaltura_login(true, '')) {
            $mform->addElement('header', 'video', get_string('video_hdr', 'kalvidres'));
			
			if (empty($this->current->entry_id)) {
                $this->add_media_definition($mform, null);
            } else {
                $this->add_media_definition($mform, $this->current->entry_id);
            }
        } else {
            $mform->addElement('static', 'connection_fail', get_string('conn_failed_alt', 'local_kaltura'));
        }


        $this->add_showpreview_option($mform);

        $mform->addElement('header', 'access_log', get_string('publish_access_log_hdr', 'kalvidres'));
        $this->add_publish_access_log_definition($mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * This function return HTML markup for progress bar.
     * @return string - HTML markup for progress bar.
     */
    private function draw_progress_bar() {
        $attr         = array('id' => 'progress_bar');
        $progress_bar = html_writer::tag('span', '', $attr);

        $attr          = array('id' => 'slider_border');
        $slider_border = html_writer::tag('div', $progress_bar, $attr);

        $attr          = array('id' => 'loading_text');
        $loading_text  = html_writer::tag('div', get_string('checkingforjava', 'mod_kalvidres'), $attr);

        $attr   = array('id' => 'progress_bar_container',
                        'style' => 'width:100%; padding-left:10px; padding-right:10px; visibility: hidden');
        $output = html_writer::tag('span', $slider_border . $loading_text, $attr);

        return $output;

    }

    /**
     * This function add "Access Log" part to module form.
     * @param object $mform - form object.
     */
    private function add_publish_access_log_definition($mform) {
        $accessgroup = array();
        $options = array('0' => 'No', '1' => 'Yes');
        $select = $mform->addElement('select', 'publish_access_log', get_string('publish_access_log', 'mod_kalvidres'), $options);
        $mform->addHelpButton('publish_access_log', 'publish_access_log', 'mod_kalvidres');

        $select->setSelected('0');

        $accessgroup[] =& $select;

        $options = array('0' => '0', '4' => '4', '8' => '8', '12' => '12', '16' => '16', '20' => '20', '24' => '24');
        $select = $mform->addElement('select', 'exclusion_time', get_string('exclusion_time', 'mod_kalvidres'), $options);
        $mform->addHelpButton('exclusion_time', 'exclusion_time', 'mod_kalvidres');
        $select->setSelected('0');

        $accessgroup[] =& $select;
    }

    /**
     * This function add "Access" part to module form.
     * @param object $mform - form object.
     */
    private function add_access_definition($mform) {
        $accessgroup = array();
        $options = array('0' => 'No', '1' => 'Yes');
        $select = $mform->addElement('select', 'internal', get_string('internal', 'mod_kalvidres'), $options);
        $select->setSelected('0');
        $accessgroup[] =& $select;
    }

	

  /**
   * This function add "show preview" part to module form.
   * @param object $mform - form object.
   */
  private function add_showpreview_option($mform) {
      $previewgroup = array();
      $options = array('0' => 'No', '1' => 'Yes');
      $select = $mform->addElement('select', 'showpreview', get_string('showpreview', 'mod_kalvidres'), $options);
      $select->setSelected('0');
      $accessgroup[] =& $select;
  }
		
    /**
     * This function add "Media" part to module form.
     * @param object $mform - form object.
     * @param string $entry_id - id of media entry.
     */
    private function add_media_definition($mform, $entry_id) {

        $thumbnail = $this->get_thumbnail_markup($entry_id);

        $mform->addElement('static', 'add_media_thumb', '&nbsp;', $thumbnail);

        if (empty($entry_id)) {
            $prop = array('style' => 'display:none;');
        }

        $mediagroup = array();

        if (local_yukaltura_get_mymedia_permission()) {
            if ($entryid == null || $entryid == '') {
                $mediagroup[] =& $mform->createElement('button', 'add_media',
                                                       get_string('add_media', 'kalvidres'), array());
            } else {
                $mediagroup[] =& $mform->createElement('button', 'add_media',
                                                       get_string('replace_media', 'kalvidres'), array());
            }
        }

        $prop = array();

        if (empty($this->current->entry_id)) {
            $prop += array('style' => 'visibility: hidden;');
        }

        $mediagroup[] =& $mform->createElement('button', 'media_properties',
                                               get_string('media_properties', 'local_yukaltura'), $prop);

        $mform->addGroup($mediagroup, 'media_group1', '&nbsp;', '&nbsp;', false);

        if (get_config(KALTURA_PLUGIN_NAME, 'kalvidres_upload') == 1 && local_yukaltura_get_mymedia_permission()) {
            $mediagroup = array();
                $str = get_string('simple_upload', 'local_yumymedia');
                $str .= ' (' . get_string('pc_recommended', 'local_yumymedia') . ')';
            $mediagroup[] =& $mform->createElement('button', 'upload_media', $str, array());
            if (get_config(KALTURA_PLUGIN_NAME, 'enable_webcam') == 1) {
                $str = get_string('webcam_upload', 'local_yumymedia');
                $str .= ' (' . get_string('pc_only', 'local_yumymedia') . ')';
                $mediagroup[] =& $mform->createElement('button', 'record_media', $str, array());
            }
            $mform->addGroup($mediagroup, 'media_group2', '&nbsp;', '&nbsp;', false);
        }
    }

    /**
     * This function return HTML markup to display popup panel.
     * @return string - HTML markup to display popup panel.
     */
    private function get_popup_markup() {

        $output = '';

        // Panel markup to set media properties.
        $attr = array('id' => 'media_properties_panel', 'style' => 'display: none;');
        $output .= html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', get_string('media_prop_header', 'kalvidres'), $attr);

        $attr = array('class' => 'bd');

        $propertiesmarkup = $this->get_media_preferences_markup();

        $output .= html_writer::tag('div', $propertiesmarkup, $attr);

        $output .= html_writer::end_tag('div');

        // Panel markup to preview media.
        $attr = array('id' => 'media_preview_panel', 'style' => 'display: none;');
        $output .= html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', get_string('media_preview_header', 'kalvidres'), $attr);

        $attr = array('class' => 'bd',
                      'id' => 'media_preview_body');

        $output .= html_writer::tag('div', '', $attr);

        return $output;
    }

    /**
     * This function return HTML markup to display thumbnail.
     * @param string $entry_id - id of media entry.
     * @return string - HTML markup to display thumbnail.
     */
    private function get_thumbnail_markup($entry_id) {
        global $CFG;

        $source = '';
        $output = '';

        /*
         * tabindex -1 is required in order for the focus event to be capture
         * amongst all browsers.
         */
        $attr = array('id' => 'notification',
                      'class' => 'notification',
                      'tabindex' => '-1');
        $output = html_writer::tag('div', '', $attr);

        $source = $CFG->wwwroot . '/local/kaltura/pix/vidThumb.png';;
        $alt    = get_string('media_select', 'kalvidres');
        $title  = get_string('media_select', 'kalvidres');

        if (!empty($entry_id)) {
			$entries = new KalturaStaticEntries();
            $entryobj = KalturaStaticEntries::getEntry($entry_id, null, false);
			//die('entryobj: '.print_r($entryobj,1).'|'.$entry_id);
            if (isset($entryobj->thumbnailUrl)) {
                $source = $entryobj->thumbnailUrl;
                $alt    = $entryobj->name;
                $title  = $entryobj->name;
            }

        }

        $attr = array('id' => 'media_thumbnail',
                      'src' => $source,
                      'alt' => $alt,
                      'title' => $title);

        $output .= html_writer::empty_tag('img', $attr);

        return $output;

    }


    /**
     * This function returns an array of video resource players.
     *
     * If the override configuration option is checked, then this function will
     * only return a single array entry with the overridden player
     * @return array - First element will be an array whose keys are player ids
     * and values are player name.  Second element will be the default selected
     * player.  The default player is determined by the Kaltura configuraiton
     * settings (local_kaltura).
     */
    private function get_video_resource_players() {

        // Get user's players
        $players = local_kaltura_get_custom_players();

        // Kaltura regular player selection
        $choices = array(KALTURA_PLAYER_PLAYERREGULARDARK  => get_string('player_regular_dark', 'local_kaltura'),
                         KALTURA_PLAYER_PLAYERREGULARLIGHT => get_string('player_regular_light', 'local_kaltura'),
                         );

        if (!empty($players)) {
            $choices = $choices + $players;
        }

        // Set default player only if the user is adding a new activity instance
        $default_player_id = local_kaltura_get_player_uiconf('player_resource');

        // If the default player id does not exist in the list of choice
        // then the user must be using a custom player id, add it to the list
        if (!array_key_exists($default_player_id, $choices)) {
            $choices = $choices + array($default_player_id => get_string('custom_player', 'kalvidres'));
        }

        // Check if player selection is globally overridden
        if (local_kaltura_get_player_override()) {
            return array(array( $default_player_id => $choices[$default_player_id]),
                         $default_player_id
                        );
        }

        return array($choices, $default_player_id);

    }

    /**
     * Create player properties panel markup.  Default values are loaded from
     * the javascript (see function "handle_cancel" in kaltura.js
     * @return string - html markup for media preferences.
     */
    private function get_media_preferences_markup() {
        $output = '';

        // Display name input box.
        $attr = array('for' => 'media_prop_name');
        $output .= html_writer::tag('label', get_string('media_prop_name', 'kalvidres'), $attr);
        $output .= '&nbsp;';

        $attr = array('type' => 'text',
                      'id' => 'media_prop_name',
                      'name' => 'media_prop_name',
                      'value' => '',
                      'maxlength' => '100');
        $output .= html_writer::empty_tag('input', $attr);
        $output .= html_writer::empty_tag('br');

        // Display section element for player design.
        $attr = array('for' => 'media_prop_player');
        $output .= html_writer::tag('label', get_string('media_prop_player', 'kalvidres'), $attr);
        $output .= '&nbsp;';

        list($options, $defaultoption) = $this->get_media_resource_players();

        $attr = array('id' => 'media_prop_player');

        $output .= html_writer::select($options, 'media_prop_player', $defaultoption, false, $attr);
        $output .= html_writer::empty_tag('br');

        // Display player dimensions radio button.
        $attr = array('for' => 'media_prop_dimensions');
        $output .= html_writer::tag('label', get_string('media_prop_dimensions', 'kalvidres'), $attr);
        $output .= '&nbsp;';

        $options = array(0 => get_string('normal', 'kalvidres'),
                         1 => get_string('widescreen', 'kalvidres')
                         );

        $attr = array('id' => 'media_prop_dimensions');
        $selected = !empty($defaults) ? $defaults['media_prop_dimensions'] : array();
        $output .= html_writer::select($options, 'media_prop_dimensions', $selected, array(), $attr);

        //$output .= html_writer::empty_tag('br');
        //$output .= html_writer::empty_tag('br');

        // Display player size drop down button
        $attr = array('for' => 'vid_prop_size','disabled'=>'disabled');
        $output .= html_writer::tag('label', get_string('vid_prop_size', 'kalvidres'), $attr);
        //$output .= '&nbsp;';

        $options = array(0 => get_string('vid_prop_size_large', 'kalvidres'),
                         1 => get_string('vid_prop_size_small', 'kalvidres'),
                         2 => get_string('vid_prop_size_custom', 'kalvidres')
                         );

        $attr = array('id' => 'vid_prop_size','disabled'=>'disabled');
        $selected = !empty($defaults) ? $defaults['vid_prop_size'] : array();

        $output .= html_writer::select($options, 'vid_prop_size', $selected, array(), $attr);

        // Display custom player size
        //$output .= '&nbsp;&nbsp;';

        $attr = array('type' => 'text',
                      'id' => 'vid_prop_width',
                      'name' => 'vid_prop_width',
                      'value' => '',
                      'maxlength' => '3',
                      'size' => '3',
                      );
        $output .= html_writer::empty_tag('input', $attr);

        //$output .= '&nbsp;x&nbsp;';

        $attr = array('type' => 'text',
                      'id' => 'vid_prop_height',
                      'name' => 'vid_prop_height',
                      'value' => '',
                      'maxlength' => '3',
                      'size' => '3',
                      );
        $output .= html_writer::empty_tag('input', $attr);

        return $output;
    }

    /**
     * This function return media properties.
     * @return array - media properties.
     */
    private function get_default_media_properties() {
        return $properties = array('media_prop_player' => 4674741,
                                   'media_prop_dimensions' => 0,
                                   'media_prop_size' => 0,
                                  );
    }

    /**
     * This function changes form information after media selected.
     */
    public function definition_after_data() {
        $mform = $this->_form;

        if (!empty($mform->_defaultValues['entry_id'])) {
            foreach ($mform->_elements as $key => $data) {

                if ($data instanceof MoodleQuickForm_group) {

                    foreach ($data->_elements as $key2 => $data2) {
                        if (0 == strcmp('media_select', $data2->_attributes['name'])) {
                            $mform->_elements[$key]->_elements[$key2]->setValue(get_string('replace_media', 'kalvidres'));
                            break;
                        }

                        if (0 == strcmp('pres_info', $data2->_attributes['name'])) {
                            $mform->_elements[$key]->_elements[$key2]->setValue('');
                            break;
                        }
                    }
                }

            }

        }

    }

    public function validation($data, $files) {
        $errors = array();
        $playerWidth = intval($data['width']);
        $playerHeight = intval($data['height']);
        if ($playerWidth < 200 || $playerHeight < 200) {
            $errors['kalvidres_invalid_size'] = get_string('invalid_size', 'mod_kalvidres');
            print_error('invalid_size', 'kalvidres');
        }
        else if ($playerWidth > 1280 || $playerHeight > 1280) {
            $errors['kalvidres_invalid_size'] = get_string('invalid_size', 'mod_kalvidres');
            print_error('invalid_size', 'kalvidres');
        }
        return $errors;
    }

}
