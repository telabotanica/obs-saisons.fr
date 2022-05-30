import domready from 'mf-js/modules/dom/ready';

domready(() => {
    Array.from(document.querySelectorAll('.dropdown-toggle')).forEach(dropdown =>
        dropdown.addEventListener('click', evt => {
            evt.preventDefault();

            Array.from(dropdown.parentElement.querySelectorAll('.dropdown-list')).forEach(
                dateItem => dateItem.classList.toggle('hidden')
            );
        })
    );
});
