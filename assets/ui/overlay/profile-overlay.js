/* *************** *
 * PROFILE OVERLAY *
 * *************** */

import {FormOverlay} from "./form-overlay";

export function ProfileOverlay(openOverlayButton) {
    FormOverlay.call(this, openOverlayButton);
}
ProfileOverlay.prototype = Object.create(FormOverlay.prototype);
ProfileOverlay.prototype.constructor = ProfileOverlay;

ProfileOverlay.prototype.init = function() {
    FormOverlay.prototype.init.call(this);
    this.form.reset();
    this.setOverlayEditForm();
    this.editFormPreSetFields();
};

ProfileOverlay.prototype.closeOverlay = function () {
    FormOverlay.prototype.closeOverlay.call(this);
    this.resetUploadFilesComponent();
};

ProfileOverlay.prototype.editFormPreSetFields = function() {
    const user = JSON.parse(this.dataAttrs.user);

    if (this.overlay.classList.contains('edit') && !!user.avatar) {
        this.fileUploadHandler.preSetFile(user.avatar);
    }
};

/* ********************* *
 * ADMIN PROFILE OVERLAY *
 * ********************* */

export function AdminProfileOverlay(openOverlayButton) {
    ProfileOverlay.call(this, openOverlayButton);
}

AdminProfileOverlay.prototype = Object.create(FormOverlay.prototype);

AdminProfileOverlay.prototype.setOverlayEditForm = function() {
    if (this.openOverlayButton.classList.contains('edit')) {
        this.form.setAttribute('action', this.dataAttrs.editionPath);
    }
};
