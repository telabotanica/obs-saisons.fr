import domready from 'mf-js/modules/dom/ready';
import {slideUp} from "../lib/slide";

domready(() => {
    const flashMessagesEl = document.getElementsByClassName('app-flashes');

    if (flashMessagesEl) {
        flashMessagesEl.forEach(flashMessage => {
            setTimeout(() => {
                slideUp(flashMessage);
            }, 5000);
        });
    }
});
