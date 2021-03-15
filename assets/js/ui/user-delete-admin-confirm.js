import domready from 'mf-js/modules/dom/ready';

domready(() => {
    const adminDeleteUserEl = document.getElementById('admin-delete-user');
    if(!!adminDeleteUserEl) {
        adminDeleteUserEl.addEventListener('click', event => {
            if (!confirm('Confirmer la suppression du compte')) {
                event.preventDefault();
            }
        });
    }
});
