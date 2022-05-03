import domready from 'mf-js/modules/dom/ready';

function resize() {
	const htmlElement = document.querySelector('html'),
		SMALL_DEVICE_CLASS = 'ods-mod-touch',
		SMALL_DEVICE_MATCH_MEDIA = '(max-width: 991px)',
		smallDevice = window.matchMedia(SMALL_DEVICE_MATCH_MEDIA).matches;

	htmlElement.classList.toggle(SMALL_DEVICE_CLASS, smallDevice);
	window.onresize = resize;
}

domready(resize);
