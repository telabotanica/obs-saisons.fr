function addModsTouchClass() {
	if (window.matchMedia('(max-width: 991px)').matches) {
		$('html').addClass('ods-mod-touch');
	} else {
		$('html').removeClass('ods-mod-touch');
	}
}

function toggleMenuSmallDevices(){
	$('.menu-img, .close-menu-img').on('click', function() {
		var menuTanslateX = ( /close/.test(this.className) ? '-' : '' ) + '280px';
		$('.menu').animate({
			right: menuTanslateX
		}, 200);
	});
}

$( document ).ready( function() {
	toggleMenuSmallDevices();
	addModsTouchClass();
	$(window).on('resize', addModsTouchClass());
});
