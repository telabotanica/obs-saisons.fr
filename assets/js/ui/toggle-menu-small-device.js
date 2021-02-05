import domready from 'mf-js/modules/dom/ready';

// open/close menu on small devices
export function toggleMenuSmallDevices(){
    $('.menu-img, .close-menu-img').on('click', function() {
        let menuTanslateX = ( /close/.test(this.className) ? '-' : '' ) + '280px';

        $('.menu').animate({
            right: menuTanslateX
        }, 200);
    });
}

domready(toggleMenuSmallDevices);
