import domready from 'mf-js/modules/dom/ready';

domready(() => {
    const legendElements =  document.querySelectorAll('.helper-legend');
    let hideButton;
    if(legendElements.length) {
        legendElements.forEach(legendEl => {
                hideButton = legendEl.querySelector('.hide-button');
                if(hideButton) {
                    hideButton.addEventListener('click', evt => {
                        evt.preventDefault();

                        legendElements.forEach(legend => legend.classList.add('hide'));
                    })
                }
            }
        );
    }
});
