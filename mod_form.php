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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

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
        global $PAGE;

        $kaltura_renderer = $PAGE->get_renderer('local_kaltura');

        $mform =& $this->_form;

        $mform->addElement('hidden', 'entry_id', '', ['id' => 'entry_id']);
        $mform->setType('entry_id', PARAM_TEXT);

        $mform->addElement('hidden', 'video_title', '', ['id' => 'video_title']);
        $mform->setType('video_title', PARAM_TEXT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'kalvidres'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('description', 'assign'));

        $mform->addElement('header', 'video', get_string('video_hdr', 'mod_kalvidres'));

        $selected_entry_text = $this->current->video_title
            ? get_string('selected_entry', 'local_kaltura', $this->current->video_title)
            : get_string('no_selected_entry', 'local_kaltura');
        $selected_entry_header = html_writer::tag('h5', $selected_entry_text, ['data-region' => 'selected-entry-header']);
        $mform->addElement('static', '', '', $selected_entry_header);

        if ($this->current->entry_id) {
            $client = \local_kaltura\kaltura_client::get_client();
            $client->setKs(\local_kaltura\kaltura_session_manager::get_user_session($client));

            $client_legacy = \local_kaltura\kaltura_client::get_client('ce');
            $client_legacy->setKs(\local_kaltura\kaltura_session_manager::get_user_session_legacy($client_legacy));

            $entry_response = \local_kaltura\kaltura_entry_manager::get_entry($client, $this->current->entry_id);
            if (!$entry_response->totalCount) {
                $entry_response = \local_kaltura\kaltura_entry_manager::get_entry($client_legacy, $this->current->entry_id);
            }
            $entryobj = $entry_response->objects[0];

            $client->session->end();
            $client_legacy->session->end();
        }
        $thumbnail_markup = $this->get_thumbnail_markup($entryobj);
        $mform->addElement('static', 'add_media_thumb', '&nbsp;', $thumbnail_markup);

        $buttongroup = [];
        $upload_dropdown_markup = $kaltura_renderer->render_from_template('local_kaltura/kaltura_upload_menu', []);
        $buttongroup[] =& $mform->createElement('button', 'add_media', get_string('media_select', 'mod_kalvidres'));
        $buttongroup[] =& $mform->createElement('html', $upload_dropdown_markup);
        $mform->addGroup($buttongroup);

        $mform->addElement('select', 'showpreview', get_string('showpreview', 'mod_kalvidres'), ['No', 'Yes']);

        $mform->addElement('header', 'access', get_string('access_hdr', 'kalvidres'));
        $mform->addElement('select', 'internal', get_string('internal', 'mod_kalvidres'), ['No', 'Yes']);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

        $client = \local_kaltura\kaltura_client::get_client('ce');
        $client->setKs(\local_kaltura\kaltura_session_manager::get_user_session_legacy($client));
        $has_ce = \local_kaltura\kaltura_entry_manager::count_entries($client) > 0;
        $PAGE->requires->js_call_amd('mod_kalvidres/kalvidres_mod_form', 'init', [
            $PAGE->context->id,
            $this->current->entry_id,
            $this->current->video_title,
            $entryobj->thumbnailUrl,
            $has_ce
        ]);
        $client->session->end();
    }

    /**
     * This function return HTML markup to display thumbnail.
     * @param \KalturaMediaEntry
     * @return string - HTML markup to display thumbnail.
     */
    private function get_thumbnail_markup($entryobj) {
        global $CFG;

        $output = html_writer::start_div();

        $output .= html_writer::empty_tag('img', [
            'id' => 'media_thumbnail',
            'src' => $entryobj ? $entryobj->thumbnailUrl : $CFG->wwwroot . '/local/kaltura/pix/vidThumb.png',
            'alt' => $entryobj ? $entryobj->name : get_string('media_select', 'kalvidres'),
            'title' => $entryobj ? $entryobj->name : get_string('media_select', 'kalvidres'), 
            'class' => 'kaltura-media-thumbnail'
        ]);

        $output .= html_writer::end_div();

        return $output;

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

}
