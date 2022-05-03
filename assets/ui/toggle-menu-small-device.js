import domready from 'mf-js/modules/dom/ready';

// open/close menu on small devices
domready(() => {
    const menuOpenButton = document.querySelector('.menu-img'),
        menuCloseButton = document.querySelector('.close-menu-img'),
        menu = document.querySelector('.menu');

    if (menu) {
        [menuOpenButton, menuCloseButton].forEach(menuControl =>{
            menuControl.addEventListener('click', () => {
                menu.classList.toggle('open', menuControl.classList.contains('menu-img'));
            });
        });
    }
});
