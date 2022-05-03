export const generateComparableFormatedDate = dateData => {
    if (/^([\d]{2}\/){2}[\d]{4}$/.test(dateData)) {
        dateData = dateData.split('/').reverse();
    } else if(/^[\d]{4}(-[\d]{2}){2}$/.test(dateData)) {
        dateData = dateData.split('-');
    } else {
        dateData = dateData
            .toISOString()
            .substr(0, 10)
            .split('-');
    }

    return dateData.join('');
};
