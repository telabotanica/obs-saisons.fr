import domready from 'mf-js/modules/dom/ready';
import dynamicCallClass from "../../lib/dynamic-call-class";
import {ObsInfosOverlay} from "./obs-infos-overlay";
import {IndividualDetailsOverlay} from "./individual-details-overlay";
import {StationOverlay} from "./station-overlay";
import {ObservationOverlay} from "./observation-overlay";
import {IndividualOverlay} from "./individual-overlay";
import {AdminProfileOverlay, ProfileOverlay} from "./profile-overlay";
import {StationLocation} from "../stations-observations/locate-station";
import {HandleFileUploads} from "../handle-file-uploads";

const classes = {
    ObsInfosOverlay,
    IndividualDetailsOverlay,
    StationOverlay,
    IndividualOverlay,
    ObservationOverlay,
    ProfileOverlay,
    AdminProfileOverlay
};

// Avoid multiple instances of external classes when only one each is needed
const overlayNeedingExt = ['observation', 'station', 'profile', 'admin-profile'];
const initExternalInstances = (
    overlayName,
    overlayInstance,
    extInstancesStorage
) => {
    const overlayEl = document.querySelector('.overlay.' + overlayName);

    if (overlayNeedingExt.includes(overlayName)) {
        if(extInstancesStorage[overlayName] === undefined) {
            overlayInstance.fileUploadHandler = new HandleFileUploads(overlayEl.querySelector('.upload-input'));
            overlayInstance.fileUploadHandler.init();
            extInstancesStorage[overlayName] = {fileUploadHandler : overlayInstance.fileUploadHandler};
            if('station' === overlayName) {
                overlayInstance.stationLocation = new StationLocation();
                overlayInstance.stationLocation.init();
                extInstancesStorage[overlayName].stationLocation = overlayInstance.stationLocation;
            }
        } else {
            overlayInstance.fileUploadHandler = extInstancesStorage[overlayName].fileUploadHandler;
            if('station' === overlayName) {
                overlayInstance.stationLocation = extInstancesStorage[overlayName].stationLocation;
            }
        }
    }

    return {
        overlayInstance: overlayInstance,
        extInstancesStorage: extInstancesStorage
    };
};

export const onOpenOverlay = () => {
    let extInstancesStorage = {};

    document.getElementsByClassName('open').forEach(openOverlayButton => {
        $(openOverlayButton).off('click').on('click', evt => {
            evt.preventDefault();
            evt.stopPropagation();

            if (openOverlayButton.classList.contains('disabled')) {//user is not logged
                window.location.href = window.location.origin + '/user/login';
            } else {
                const overlayName = openOverlayButton.dataset.open;

                try {
                    // instantiate the right overlay class each time one is required
                    const overlayClass = dynamicCallClass(classes, overlayName, 'Overlay'),
                        // Avoid multiple instances of external classes when only one each is needed
                        instances = initExternalInstances(
                            overlayName,
                            new overlayClass(openOverlayButton),
                            extInstancesStorage
                        );

                    extInstancesStorage = instances.extInstancesStorage;

                    instances.overlayInstance.init();

                } catch (error) {
                    console.warn(`Une erreur s’est produite lors de l’ouverture de l’overlay "${overlayName}"`);
                    console.warn(error);
                }
            }
        });
    });
};

/* *********** *
 *  DOM READY   *
 * *********** */

domready(onOpenOverlay);
