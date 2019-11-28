//touch or desktop device
function addModsTouchClass() {
	$('html').toggleClass('ods-mod-touch', window.matchMedia('(max-width: 991px)').matches);
	$(window).off('resize').on('resize', addModsTouchClass);
}

// open/close menu on small devices
function toggleMenuSmallDevices(){
	$('.menu-img, .close-menu-img').on('click', function() {
		var menuTanslateX = ( /close/.test(this.className) ? '-' : '' ) + '280px';

		$('.menu').animate({
			right: menuTanslateX
		}, 200);
	});
}

// open/close overlay
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

// switch between tabs
function switchTabs() {
	var activeTab = $('.tabs-holder').data('active');

	if(activeTab !== 'all') {
		$('[data-tab]:not(.tab)').each( function () {
			if(activeTab !== $(this).data('tab')) {
				$(this).hide();

			}
		});

	}

	$('.tab').off('click').on('click', function (event) {
		event.preventDefault();

		activeTab = $(this).data('tab');

		$('.tabs-holder')
			.data('active', activeTab)
			.attr('data-active', activeTab);

		$('[data-tab]').each(function (i, element) {
			var $element = $(element);

			if ($element.hasClass('tab')) {
				if (activeTab === $element.data('tab')){
					$element.removeClass('not');

				} else {
					$element.addClass('not');

				}

			} else {
				var toggleElement = ('all' === activeTab || $element.data('tab') === activeTab);
				// for the case of observations
				if (typeof $element.data('year') !== 'undefined') {
					var activeDate = $element.closest('.table-container').find('.active-year').text();

					toggleElement = observationsToggleCombinedConditions($element, activeDate, toggleElement);

				}
				if(toggleElement) {
					$element.show(200);

				} else {
					$element.hide(200);

				}

			}
		});
	});
}

// Open/close calendar
function toggleCalendar() {
	$('a.item-heading-dropdown').off('click').on('click', function (event) {
		event.preventDefault();

		var id = $(this).closest('.list-cards-item').data('id');

		$(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
		$('.periods-calendar[data-id="' + id + '"]').toggle(200);
	});

	$('.table-mask-button').off('click').on('click', function (event) {
		event.preventDefault();

		var id = $(this).closest('.periods-calendar').data('id');

		$('.list-cards-item[data-id="' + id + '"] a.item-heading-dropdown').trigger('click');
	});
}

// open/close date selection
function toggleDateSelection() {
	$('.dropdown-toggle').off('click').on('click', function (event) {
		event.preventDefault();

		$(this).siblings('.dropdown-list').toggleClass('hidden');
	})
}

// select new date and show/hide observations
function calendarSwitchDate() {
	$('.dropdown-link').off('click').on('click', function (event) {
		event.preventDefault();

		var $thisCalendar = $(this).closest('.periods-calendar'),
			activeDate = $(this).text();

		$('.active-year', $thisCalendar).text(activeDate);
		$('.dropdown-link.hidden', $thisCalendar).removeClass('hidden');
		$(this).addClass('hidden');
		$('.dropdown-list', $thisCalendar).addClass('hidden');

		// show/hide observations
		var visibleObsCount = 0;
		$('.stade-marker', $thisCalendar).each( function () {
			var $element = $(this);
			if(observationsToggleCombinedConditions($element, activeDate)) {
				$element.show(200);

			} else {
				$element.hide(200);

			}
		});
	});
}

function observationsToggleCombinedConditions ($element, activeDate, matchsTab = undefined) {
	if(matchsTab === undefined) {
		var activeTab = $('.tabs-holder').data('active');

		matchsTab = ('all' === activeTab || $element.data('tab') === activeTab);

	}
	return ($element.data('year').toString() === activeDate && matchsTab );
}

function hideCalendarLegend () {
	$('.helper-legend .hide-button').click(function (event) {
		event.preventDefault();

		$('.pages-container').find('.helper-legend').hide(200);
	})
}

function toggleAccodionBlock() {
	$('a.accordion-title-dropdown').off('click').on('click', function (event) {
		event.preventDefault();

		var $thisBlock = $(this).closest('.accordion-block');

		$(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
		$('.accordion-content', $thisBlock).toggle(200);
	});
}

function switchToNextOnHomepage() {
	$('.nav-arrow').off('click').on('click', function (event) {
		event.preventDefault();

		var $thisBlock = $(this).closest('.nav-arrow-buttons'),
			targetClass = '.'+$thisBlock.data('target'),
			$visibleTargetPost = $(targetClass).not('.hidden'),
			isActuUne = (targetClass === '.actu-une-container'),
			direction = $(this).data('direction');

		function findNextTarget($element, targetClass, direction) {
			$ret = (direction === 'prev') ? $element.prev(targetClass) : $element.next(targetClass);
			valid = ( null !== $ret && undefined !== $ret && !$.isEmptyObject( $ret ) );
			if (  null !== $ret && undefined !== $ret.length ) {
				valid = ( valid  && 0 < $ret.length );
			}
			return valid ? $ret : valid;
		}

		$visibleTargetPost.addClass('hidden');
		$thisBlock.find('.nav-arrow.inactive').removeClass('inactive');
		var $newTargetPost = findNextTarget($visibleTargetPost, targetClass, direction);
		if ($newTargetPost) {
			if (!findNextTarget($newTargetPost, targetClass, direction)) {
				$thisBlock.find('.nav-arrow.'+direction).addClass('inactive');
			}
			$newTargetPost.removeClass('hidden');
			if (isActuUne) {
				var imageClass = '.actus-une-img',
					$visibleTargetImage = $(imageClass).not('.hidden');
				$visibleTargetImage.addClass('hidden');
				$newTargetImage = findNextTarget($visibleTargetImage, imageClass, direction);
				if ($newTargetImage) {
					$newTargetImage.removeClass('hidden');
				}
			}
		}
	});
}

$( document ).ready( function() {
	addModsTouchClass();
	toggleMenuSmallDevices();
	toggleOverlay();
	switchToNextOnHomepage();
	switchTabs();
	toggleCalendar();
	toggleDateSelection();
	calendarSwitchDate();
	hideCalendarLegend();
	toggleAccodionBlock();
});
