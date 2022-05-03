export default function dynamicCallClass(classes, dataAttrClassName, suffix = '') {
    let nameParts = dataAttrClassName.split('-');

    nameParts = nameParts.map(part => part.charAt(0).toUpperCase() + part.slice(1));

    const className = nameParts.join('') + suffix;

    return classes[className];
}
