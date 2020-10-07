import $ from 'jquery';

import Notification from 'core/notification';
import {subscribe} from 'core/pubsub';
import {get_string as getString} from 'core/str';

import ModalFactory from 'core/modal_factory';
import ModalVideoPicker from 'local_kaltura/modal_video_picker';
import ModalVideoPickerEvents from 'local_kaltura/modal_video_picker_events';
import ModalKalturaUpload from 'local_kaltura/modal_kaltura_upload';

import KalturaEvents from 'local_kaltura/kaltura_events';
import KalturaAjax from 'local_kaltura/kaltura_ajax';

const SELECTORS = {
    OPEN_VIDEO_PICKER: '#id_add_media',
    OPEN_UPLOAD_MODAL: '[data-action="upload"]',
    ENTRY_ID: '#entry_id',
    VIDEO_TITLE: '#video_title',
    ENTRY_THUMBNAIL: '#media_thumbnail',
    SELECTED_ENTRY_HEADER: '[data-region="selected-entry-header"]'
};

export const init = async (contextid, entryid, entryname, entrythumbnail) => {
    try {
        const [modal, uploadModal] = await Promise.all([
            ModalFactory.create({type: ModalVideoPicker.getType()}),
            ModalFactory.create({type: ModalKalturaUpload.getType()})
        ]);

        modal.contextid = contextid;
        modal.selectedEntryId = entryid;
        modal.selectedEntryName = entryname;
        modal.selectedEntryThumbnail = entrythumbnail;

        uploadModal.contextid = contextid;

        registerEventListeners(modal, uploadModal, contextid);
    } catch(error) {
        Notification.exception(error);
    }
};

const registerEventListeners = (modal, uploadModal, contextid) => {

    $(SELECTORS.OPEN_VIDEO_PICKER).on('click', () => {
        modal.show();
    });

    $(SELECTORS.OPEN_UPLOAD_MODAL).on('click', (e) => {
        e.preventDefault();
        const uploadFormType = $(e.currentTarget).attr('data-upload-type');
        uploadModal.renderUploadForm(uploadFormType);
        uploadModal.show();
    });

    subscribe(ModalVideoPickerEvents.entrySelected, async (entry) => {
        $(SELECTORS.ENTRY_ID).val(entry.entryId);
        $(SELECTORS.ENTRY_THUMBNAIL).attr('src', entry.entryThumbnail);
        $(SELECTORS.VIDEO_TITLE).val(entry.entryName);
        const selectedEntryText = await getString('selected_entry', 'local_kaltura', entry.entryName);
        $(SELECTORS.SELECTED_ENTRY_HEADER).text(selectedEntryText);
    });

    subscribe(KalturaEvents.uploadComplete, async (entryid) => {
        uploadModal.hide();
        const entry = await KalturaAjax.getEntry(contextid, entryid);
        $(SELECTORS.ENTRY_ID).val(entry.id);
        $(SELECTORS.ENTRY_THUMBNAIL).attr('src', entry.thumbnailUrl + '/width/360/height/200/type/3');
        $(SELECTORS.VIDEO_TITLE).val(entry.name);
        const selectedEntryText = await getString('selected_entry', 'local_kaltura', entry.name);
        $(SELECTORS.SELECTED_ENTRY_HEADER).text(selectedEntryText);
    });

};
