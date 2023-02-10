export const onDeleteButton = subject => {
    Array.from(document.getElementsByClassName('delete-button')).forEach(deleteButton => {
        const clone = deleteButton.cloneNode(true);

        deleteButton.replaceWith(clone);
        clone.addEventListener('click', evt => {
            let question = 'Êtes vous sûr de vouloir supprimer ce';

            switch (subject) {
                case 'obs-infos':
                    subject = 'observation ?';
                case 'station':
                    question += 'tte station ? Toutes les données liées à cette station (observations et' +
                        ' individus) seront' +
                        ' supprimées' +
                        ' définitivement. Si vous souhaitez conserver ses données veuillez annuler la' +
                        ' suppression et vous désactiver la station sur la page correspondante';
                    break;
                case 'observation':
                    question += 'tte '+subject + '?';
                    break;
                case 'individual':
                    question += 't individu ?';
                    break;
                default:
                    question += 't élément ?';
                    break;
            }
            if(!confirm(question)) {
                evt.preventDefault();
            }
        })
    });
};
