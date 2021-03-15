// Decode html entities
String.prototype.htmlEntitiesODS = function () {
    const temp = document.createElement("div");

    temp.innerHTML = this;

    const result = temp.childNodes[0].nodeValue;

    temp.removeChild(temp.firstChild);

    return result;
};
