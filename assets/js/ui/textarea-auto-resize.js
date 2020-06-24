$( document ).ready( () => {
    $('.html-editor > textarea').keyup(function (e) {
        let rows = $(this).val().split("\n");
        $(this).prop('rows', rows.length);
    }).keyup();
});
