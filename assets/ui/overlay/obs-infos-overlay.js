/* *********************** *
 * OBSERVATION INFORMATION *
 * *********************** */

import {onOpenOverlay} from "./overlay-open";
import {Overlay} from "./overlay";
import domready from 'mf-js/modules/dom/ready';

domready(() => {
    var buttons = $('.dual-squared-button.edit-calendar-icon.open');
    var container = $('.dual-blocks-container');
    if (buttons.length > 0){
        for (var i = 0;i<buttons.length;i++){
            
            if (buttons[i].getAttribute('data-individuals-deaths')==1){
                container[i].removeChild(buttons[i]);
                
            }

        }
    }
});
export function ObsInfosOverlay(openOverlayButton) {
    Overlay.call(this, openOverlayButton);
}

ObsInfosOverlay.prototype = Object.create(Overlay.prototype);

ObsInfosOverlay.prototype.constructor = ObsInfosOverlay;

ObsInfosOverlay.prototype.init = function() {
    Overlay.prototype.init.call(this);

    this.onObsInfo();
};

ObsInfosOverlay.prototype.onObsInfo = function () {
    const lthis = this;
    const observationElements = Array.from(
            this.openOverlayButton.closest('.periods-calendar').querySelectorAll('.stage-marker')
        ),
        observationDatasetKeysCriteria = ['stage', 'individualId', 'year', 'month'];

    const theseObservations = observationElements.filter(observation => {
        if (observation.classList.contains('hide')) {
            return false;
        }
        return observationDatasetKeysCriteria.every(function (key) {
            return lthis.dataAttrs[key] === observation.dataset[key];
        });
    });
    const obsInfoEl = document.querySelector('.obs-informations');
    let obsInfoTitle = 'Détails de l’observation';

    while(obsInfoEl.firstChild) {
        obsInfoEl.removeChild(obsInfoEl.firstChild);
    }

    if(this.openOverlayButton.classList.contains('absence') && 1 === theseObservations.length) {
        obsInfoTitle = 'Signalement d’absence de ce stade';
    } else if (!!theseObservations) {
        obsInfoTitle = 'Détails des observations';
    }

    theseObservations.forEach(observation => {
        obsInfoEl.append(lthis.observationListCardHtmlGenerate(observation.dataset));
        onOpenOverlay();
    });

    obsInfoEl.textcontent = obsInfoTitle;
};

ObsInfosOverlay.prototype.observationListCardHtmlGenerate = function() {
    const observation = JSON.parse(this.dataAttrs.observation),
        listCardItem = document.createElement('div');
    let editButtons = '';

    listCardItem.classList.add('list-cards-item', 'obs');
    listCardItem.dataset.id = observation.id;

    if(this.dataAttrs?.showEdit) {
        editButtons =
            `<div class="dual-blocks-container">
                <a href="" class="dual-squared-button edit-obs edit-list-icon edit open" data-open="observation" data-observation-id="${observation.id}">
                    <div class="squared-button-label">Éditer</div>
                </a>
                <a id="obs_delete_btn" href="/observation/${observation.id}/delete" class="dual-squared-button delete-icon delete-button" onclick="return confirm('Êtes-vous sûr-e de vouloir supprimer cette observation ?' )">
                    <div class="squared-button-label">Supprimer</div>
                </a>
            </div>`
        ;
    }
   
    listCardItem.innerHTML =
        `<a href="${this.dataAttrs.pictureUrl}" class="list-card-img" style="background-image:url(${this.dataAttrs.pictureUrl})" target="_blank"></a>
        <div class="item-name-block">
            <div class="item-name">${observation.user.displayName}</div>
            <div class="item-name stage">${this.dataAttrs.stage}</div>
            <div class="item-heading-dropdown">${this.dataAttrs.date}</div>
        </div>
        ${editButtons}`;
    
    return listCardItem;
};

ObsInfosOverlay.prototype.closeOverlay = function () {
    Overlay.prototype.closeOverlay.call(this);
    this.overlay.textcontent = '';
};

ObsInfosOverlay.prototype.closeOverlayOnClickOut = function () {
    const lthis = this;
    this.overlay.addEventListener('click', function(evt) {
        if(!evt.target.closest('.obs-info-container')) {
            lthis.closeOverlay();
        }
    });
};


