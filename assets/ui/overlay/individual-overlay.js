/* ****************** *
 * INDIVIDUAL OVERLAY *
 * ****************** */

import {FormOverlay} from "./form-overlay";
import {parseDatasetValToBool} from "../../lib/parse-to-bool";

export function IndividualOverlay(openOverlayButton) {
    FormOverlay.call(this, openOverlayButton);

    this.individualData = {};
    this.speciesEl = document.getElementById('individual_species');
}
IndividualOverlay.prototype = Object.create(FormOverlay.prototype);
IndividualOverlay.prototype.constructor = IndividualOverlay;

IndividualOverlay.prototype.init = function() {
    FormOverlay.prototype.init.call(this);

    this.manageSpecies();
    this.editFormPreSetFields();
};

IndividualOverlay.prototype.setOverlayEditForm = function() {
    if (this.openOverlayButton.classList.contains('edit')) {
        this.individualData = JSON.parse(this.dataAttrs.individual);

        const individualPath =  '/individual/',
            formActionReset = `/station/${this.individualData.station.id}${individualPath}new`,
            editionPath = individualPath + this.individualData.id;

        this.overlay.querySelector('.show-on-edit').href = editionPath + '/delete';
        this.overlay.classList.add('edit');
        this.form.action = editionPath + '/edit';
        this.form.dataset.formActionReset = formActionReset;
    }
};

IndividualOverlay.prototype.editFormPreSetFields = function() {
    if (this.overlay.classList.contains('edit')) {
        this.overlay.querySelector('.saisie-header').textContent = 'Modifier l’individu';

        if (this.individualData.name) {
            document.getElementById('individual_name').value = this.individualData.name;
        }
        if (this.individualData.details) {
            document.getElementById('individual_details').value = this.individualData.details;
        }

        this.speciesEl.classList.remove('disabled');
        if (!!this.individualData.species.id || 0 === this.individualData.species.id) {
            this.selectOption(
                this.speciesEl.querySelector('.species-option.species-' + this.individualData.species.id)
            );
        }
    }
};

IndividualOverlay.prototype.manageSpecies = function() {
    const helpEl = document.getElementById('individual_species_help'),
        species = this.dataAttrs.species || '',
        availableSpecies = this.getDataAttrValuesArray(species.toString()) || null,
        showAll = parseDatasetValToBool(this.dataAttrs.allSpecies);

    // toggle marker and help text on already recorded species in station
    helpEl.classList.toggle('hidden',!showAll || !species);
    this.speciesEl.getElementsByClassName('exists-in-station').forEach(element => {
        const speciesNameText = element.textContent;
        if (!showAll && /\(\+\)/.test(speciesNameText)) {
            element.textContent = speciesNameText.replace(' (+)', '');
        } else if (showAll && !/\(\+\)/.test(speciesNameText)) {
            element.textContent = speciesNameText+' (+)';
        }
    });

    this.updateSelectOptions(this.speciesEl, availableSpecies, !showAll);
};

IndividualOverlay.prototype.updateSelectOptions = function(
    selectEl,
    itemsToMatch,
    sortOptions = true
) {
    FormOverlay.prototype.updateSelectOptions.call(this, selectEl, itemsToMatch, sortOptions);

    selectEl.querySelectorAll('.exists-in-station.animal').forEach(option => {
        option.toggleAttribute('disabled', !sortOptions);
    });
};

IndividualOverlay.prototype.closeOverlay = function () {
    FormOverlay.prototype.closeOverlay.call(this);
    this.manageSpecies(
        document.querySelector('.open-individual-form-all-station').dataset.species
        // this element dataset.species contains all station species
    );
};
