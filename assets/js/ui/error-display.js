export const displayError = ($field, errorMessage, classAttr) => {
    $field
        .val('')
        .after(
            '<p class="'+classAttr+' field-help-text help-text" style="color:red;">' + errorMessage + '</p>'
        )
    ;
    setTimeout(function () {
        $('.'+classAttr).hide(200, function () {
            $(this).remove();
        });
    }, 3000);
};
