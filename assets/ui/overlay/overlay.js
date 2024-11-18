import {onDeleteButton} from "../handle-delete-button";

export function Overlay(openOverlayButton) {
    this.openOverlayButton = openOverlayButton;
    this.dataAttrs = this.openOverlayButton.dataset;
    this.overlay = document.querySelector('.overlay.' + this.dataAttrs.open);
    this.form = this.overlay.querySelector('form');
}

Overlay.prototype.init = function() {
    this.overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    this.closeOverlayOnClickOut();
    this.closeOverlayOnButtonClick();
    this.closeOverlayOnEscapeKey();
    onDeleteButton(this.dataAttrs.open);
};

Overlay.prototype.closeOverlay = function() {
    document.body.style.overflow = 'auto';
    this.overlay.classList.add('hidden');
};

Overlay.prototype.closeOverlayOnButtonClick = function() {
    const lthis = this;

    document.getElementById('cancel').addEventListener('click', evt => {
        evt.preventDefault();

        lthis.closeOverlay();
    });
};

Overlay.prototype.closeOverlayOnClickOut = function() {
    const lthis = this;
    this.overlay.addEventListener('click', function(evt) {
        if(!evt.target.closest('.saisie-container')) {
            lthis.closeOverlay();
        }
    });
};

Overlay.prototype.closeOverlayOnEscapeKey = function() {
    const lthis = this;
    document.body.addEventListener('keydown', function(evt) {
        const ESC_KEY_STRING = /^Esc(ape)?/;

        if(27 === evt.keyCode || ESC_KEY_STRING.test(evt.key)) {
            lthis.closeOverlay();
        }
    });
};
