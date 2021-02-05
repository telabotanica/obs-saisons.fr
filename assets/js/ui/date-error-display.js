global.displayDateError = ($field, errorMessage) => {
    $field
        .val('')
        .after(
            '<p class="invalid-date field-help-text help-text" style="color:red;">' + errorMessage + '</p>'
        )
    ;
    setTimeout(function () {
        $('.invalid-date').hide(200, function () {
            $(this).remove();
        });
    }, 3000);
};
