import domready from 'mf-js/modules/dom/ready';

const HTML_ELEMENT = document.querySelector('html');
const SMALL_DEVICE_CLASS = 'ods-mod-touch';
const SMALL_DEVICE_MATCH_MEDIA = '(max-width: 991px)';

export function resize() {
	let classList = HTML_ELEMENT.classList,
		smallDevice = window.matchMedia(SMALL_DEVICE_MATCH_MEDIA).matches;

	if (smallDevice) {
		classList.add(SMALL_DEVICE_CLASS);
	} else {
		classList.remove(SMALL_DEVICE_CLASS);
	}
	window.onresize = resize;
}

domready(resize);

