$( document ).ready( () => {
    const scientificName = Array.from(document.querySelectorAll('.scientific-name'));
    const reScientificName = /^([\w\s]*)(?!\w*\.)/;

    if (scientificName.length > 0) {
        scientificName.forEach( ( scientificName ) => {
            const txt = scientificName.textContent;
            const found = txt.match(reScientificName);

            scientificName.innerHTML = txt.replace(found[0], '<i>'+found[0]+'</i>');
        });
    }
});
