global.valOk = (
    value,
    comparisonDirection = true,
    compareTo = null
) => {
    let result = true;
    if ('boolean' !== typeof value) {
        switch(typeof value) {
            case 'string' :
                result = ('' !== value);
                break;
            case 'number' :
                result = (!isNaN(value));
                break;
            case 'object' :
                result = (null !== value && undefined !== value && !$.isEmptyObject(value));
                if (null !== value && undefined !== value.length) {
                    result = (result && 0 < value.length);
                }
                break;
            case 'undefined' :
            default :
                result = false;
        }
        if (result && compareTo !== null) {
            var comparisonResult = (compareTo === value);
            result = (comparisonDirection) ? comparisonResult : !comparisonResult;
        }
        return result;
    } else {
        // Boolean is valid value
        return true;
    }
};
