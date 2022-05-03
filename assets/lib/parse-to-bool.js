export const parseDatasetValToBool = val => {
    return "boolean" === typeof val ? val : ['true', 1, "1"].includes(val);
};
