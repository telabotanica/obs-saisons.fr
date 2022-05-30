import domready from 'mf-js/modules/dom/ready';

function getNodeNextElement(nodeList, element, direction) {
    const elementNodeIndex =  Array.from(nodeList).indexOf(element),
        lastNodeListIndex = nodeList.length -1;
    let index;

    if ('next' === direction) {
        index = lastNodeListIndex > elementNodeIndex ? (elementNodeIndex + 1) : 0;
    } else {
        index = 0 < elementNodeIndex ? (elementNodeIndex - 1) : lastNodeListIndex;
    }

    return  nodeList[index];
}

domready(() => {
    Array.from(document.getElementsByClassName('nav-arrow')).forEach(
        navArrow => navArrow.addEventListener('click', function (evt) {
            evt.preventDefault();

            const arrowsContainer = navArrow.closest('.nav-arrow-buttons'),
                targetClass = arrowsContainer.dataset.target,
                targetNodeList = document.getElementsByClassName(targetClass),
                visibleTargetPost = document.querySelector('.'+targetClass+':not(.hidden)'),
                direction = navArrow.dataset.direction;

            if(2 > targetNodeList.length) {
                Array.from(arrowsContainer.getElementsByClassName('nav-arrow')).forEach(
                    arrow => arrow.classList.add('inactive')
                );
            }

            if(!!visibleTargetPost && !!targetNodeList && 1 < targetNodeList.length) {
                visibleTargetPost.classList.add('hidden');
                Array.from(arrowsContainer.querySelectorAll('.nav-arrow.inactive')).forEach(
                    arrow => arrow.classList.remove('inactive')
                );

                const newTargetPost = getNodeNextElement(
                    targetNodeList,
                    visibleTargetPost,
                    direction
                );

                newTargetPost.classList.remove('hidden');
                if ('actu-une-container' === targetClass) {
                    const imageClass = 'actus-une-img',
                        visibleTargetImage = document.querySelector('.'+imageClass+':not(.hidden)'),
                        imagesNodeList = document.getElementsByClassName(imageClass),
                        newTargetImage = getNodeNextElement(
                            imagesNodeList,
                            visibleTargetImage,
                            direction
                        );

                    visibleTargetImage.classList.add('hidden');
                    if(!!newTargetImage) {
                        newTargetImage.classList.remove('hidden');
                    }
                }

            }
        })
    );
});
