import domready from 'mf-js/modules/dom/ready';

domready(() => {
    document.querySelectorAll('.dropdown-toggle').forEach(dropdown =>
        dropdown.addEventListener('click', evt => {
            evt.preventDefault();

            dropdown.parentElement.querySelectorAll('.dropdown-list').forEach(
                dateItem => dateItem.classList.toggle('hidden')
            );
        })
    );
});
