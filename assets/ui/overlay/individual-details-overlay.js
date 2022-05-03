/* ****************** *
 * INDIVIDUAL DETAILS *
 * ****************** */

import {onOpenOverlay} from "./overlay-open";
import {Overlay} from "./overlay";

export function IndividualDetailsOverlay(openOverlayButton) {
    Overlay.call(this, openOverlayButton);
}

IndividualDetailsOverlay.prototype = Object.create(Overlay.prototype);

IndividualDetailsOverlay.prototype.constructor = IndividualDetailsOverlay;

IndividualDetailsOverlay.prototype.init = function() {
    Overlay.prototype.init.call(this);

    this.onIndividualDetails();
};

IndividualDetailsOverlay.prototype.onIndividualDetails = function () {
    if (!!this.dataAttrs.details) {
        document.getElementById('individual-details-block').textContent = this.dataAttrs.details;
        onOpenOverlay();
    }
};

IndividualDetailsOverlay.prototype.closeOverlay = function () {
    Overlay.prototype.closeOverlay.call(this);
};

IndividualDetailsOverlay.prototype.closeOverlayOnClickOut = function () {
    const lthis = this;
    this.overlay.addEventListener('click', function(evt) {
        if(!evt.target.closest('.individual-details-container')) {
            lthis.closeOverlay();
        }
    });
};
