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

function toggleOverlay() {
	$('a.open').on('click', function (event) {
		event.preventDefault();
		var id = $(this).data('open');
		$('.overlay.' + id).removeClass('hidden');
	});

	$('a.bt-annuler').on('click', function (event) {
		event.preventDefault();
		$(this).closest('.overlay').addClass('hidden');
	});
}

function toggleDropdown() {
	$('.dropdown-toggle').off('click').on('click', function (event) {
		event.preventDefault();
		$(this).siblings('.dropdown-list').toggleClass('hidden');
	})
}

function toggleCalendar() {
	$('.item-heading-dropdown').off('click').on('click', function (event) {
		event.preventDefault();
		var id = $(this).closest('.list-cards-item').data('id');

		$(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
		$('.periods-calendar[data-id="' + id + '"]').toggleClass('hidden');
	});

	$('.table-mask-button').off('click').on('click', function (event) {
		event.preventDefault();
		$(this).closest('.periods-calendar').addClass('hidden');
	});
}

function calendarSwitchDate() {
	$('.dropdown-link').on('click', function (event) {
		event.preventDefault();
		var thisCalendar = $(this).closest('.dropdown'),
			date = $(this).text();

		$('.active-year', thisCalendar).text(date);
		$('.dropdown-link.hidden', thisCalendar).removeClass('hidden');
		$(this).addClass('hidden');
		$('.dropdown-list', thisCalendar).addClass('hidden');
	})
	// todo: throw event to get that years datas or call datas with ajax
}

$( document ).ready( function() {
	toggleMenuSmallDevices();
	toggleOverlay();
	toggleDropdown();
	toggleCalendar();
	calendarSwitchDate();
	addModsTouchClass();
	$(window).on('resize', addModsTouchClass());
});
