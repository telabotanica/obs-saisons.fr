import domready from 'mf-js/modules/dom/ready';

domready(() => {
    const $adminDeleteUser = $('#admin-delete-user');
    if(0 < $adminDeleteUser.length) {
        $adminDeleteUser.on('click', function (event) {
            if (!confirm('Confirmer la suppression du compte')) {
                event.preventDefault();
            }
        });
    }
});
