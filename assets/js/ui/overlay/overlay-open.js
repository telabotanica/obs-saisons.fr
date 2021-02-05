import domready from 'mf-js/modules/dom/ready';

// open overlay
domready(() => {
    export var placesAutocomplete = {};
    if (0 < $('.ods-places').length) {
        placesAutocomplete = placesInit();
    }
    $('a.open').off('click').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $thisLink = $(this);

        if ($thisLink.hasClass('disabled')) {//user is not logged
            window.location.href = window.location.origin+'/user/login';
        } else {
            let dataAttrs = $thisLink.data(),
                $overlay = $('.overlay.'+dataAttrs.open);

            $overlay
                .removeClass('hidden')
                // triggers onCloseOverlay() when clicking out of container
                .on('click', function(event) {
                    if(!$(event.target).closest('.saisie-container, .obs-info-container').length) {
                        $('a.bt-annuler').trigger('click');
                    }
                })
            ;
            if(valOk($('form', $overlay))) {

                let $form = $('form', $overlay);
                $form.get(0).reset();
                if ($thisLink.hasClass('edit')) {
                    dataAttrs = setEditOverlayForm($overlay, $form, $thisLink, dataAttrs);
                }
            }

            $('body').css('overflow', 'hidden');
            switch(dataAttrs.open) {
                case 'admin-profile':
                    editProfilePreSetFields(dataAttrs);
                    break;
                case 'obs-infos':
                    onObsInfo($thisLink, dataAttrs);
                    break;
                case 'station':
                    onLocation(placesAutocomplete);
                    toggleMap();
                    editStationPreSetFields(dataAttrs);
                    break;
                case 'observation':
                    openDetailsField();
                    onChangeSetIndividual();
                    onChangeObsEvent();
                    onChangeObsDate();
                    observationOvelayManageIndividualAndEvents(dataAttrs);
                    editObservationPreSetFields(dataAttrs);
                    break;
                case 'individual':
                    individualOvelayManageSpecies(dataAttrs);
                    editIndividualPreSetFields(dataAttrs);
                    break;
                case 'profile':
                    editProfilePreSetFields(dataAttrs);
                    break;
                default:
                    break;
            }
            onDeleteButton(dataAttrs.open);
        }
    });
});
