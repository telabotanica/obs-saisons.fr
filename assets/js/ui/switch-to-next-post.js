import domready from 'mf-js/modules/dom/ready';

export function findNextTarget($element, targetClass, direction) {
    let $nextDisplayedElement = $element.next(targetClass).length ? $element.next(targetClass) : $(targetClass).first();
    if('prev' === direction) {
        $nextDisplayedElement = $element.prev(targetClass).length ? $element.prev(targetClass) : $(targetClass).last();
    }
    return $nextDisplayedElement;
}

domready(() => {
    $('.nav-arrow').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisBlock = $(this).closest('.nav-arrow-buttons'),
            targetClass = '.'+$thisBlock.data('target'),
            $visibleTargetPost = $(targetClass).not('.hidden'),
            isActuUne = (targetClass === '.actu-une-container'),
            direction = $(this).data('direction');

        $visibleTargetPost.addClass('hidden');
        $thisBlock.find('.nav-arrow.inactive').removeClass('inactive');

        let $newTargetPost = findNextTarget($visibleTargetPost, targetClass, direction);

        if ($newTargetPost) {
            if (!findNextTarget($newTargetPost, targetClass, direction)) {
                $thisBlock.find('.nav-arrow.'+direction).addClass('inactive');
            }
            $newTargetPost.removeClass('hidden');
            if (isActuUne) {
                let imageClass = '.actus-une-img',
                    $visibleTargetImage = $(imageClass).not('.hidden');
                $visibleTargetImage.addClass('hidden');
                let $newTargetImage = findNextTarget($visibleTargetImage, imageClass, direction);
                if ($newTargetImage) {
                    $newTargetImage.removeClass('hidden');
                }
            }
        }
    });
});
