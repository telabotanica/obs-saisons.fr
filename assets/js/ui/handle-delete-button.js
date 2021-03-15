export const onDeleteButton = subject => {
    document.getElementsByClassName('delete-button').forEach(deleteButton => {
        deleteButton.addEventListener('click', evt => {

            let question = 'Êtes vous sûr de vouloir supprimer ce';

            switch (subject) {
                case 'obs-infos':
                    subject = 'observation';
                case 'station':
                case 'observation':
                    question += 'tte '+subject;
                    break;
                case 'individual':
                    question += 't individu';
                    break;
                default:
                    question += 't élément';
                    break;
            }
            question += '?';
            if(!confirm(question)) {
                evt.preventDefault();
            }
        })
    });
};
