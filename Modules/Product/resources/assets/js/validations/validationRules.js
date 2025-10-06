import moment from 'moment';

export const requiredRule = [(v) => !!v || "This field is required"];
export const optionalRequiredRule = (v) => !!v || "This field is required";

export const minLengthRule = (minLength) => [
    (v) =>
        (v && v.length >= minLength) || `Minimum length is ${minLength} characters`,
];

export const confirmPasswordMatchRule = (password) => [
    (v) => !!v || "This field is required",
    (v) => v === password || "Passwords do not match",
];

export const onlyAlphabetsRule = [
    (v) => !v || /^[A-Za-z]+(?:\s[A-Za-z]+)*$/.test(v) || "Only alphabets and single spaces between words are allowed.",
];

export const emailRule = [
    (v) => !v || /.+@.+\..+/.test(v) || "Email must be valid",
];

export const numberOnlyRule = [
    (v) => !!v || "This field is required and digit only",
];

export const validateNumberUptoTwoDecimal = [
    (v) => !v || /^\d*(\.\d{1,2})?$/.test(v) || "Enter valid number upto two decimal point",
];

export const zeroPositiveNumberRule = [
    (v) => !!v || "This field is required",
    (v) => /^\d+$/.test(v) || "Only positive numbers are allowed", // Matches only digits (0 and positive numbers)
    (v) => v >= 0 || "Value must be 0 or a positive number",
];

export const validateMobileNumber = [
    (v) => !v || /^\d{10}$/.test(v) || "Please enter 10 digit mobile number", // Ensures the input is exactly 10 digits
];

export const inputNumberRestrict = (input, maxLength) => {
    const numericValue = input.replace(/\D/g, ''); // Remove non-numeric characters
    return numericValue.length > maxLength ? numericValue.slice(0, maxLength) : numericValue;
};

export const validateDate = (value, fieldName) => {
    if (!value) {
        return { isValid: false, error: `${fieldName} is required.` };
    }

    const today = moment().startOf('day'); // Today's date without time
    const selectedDate = moment(value).startOf('day'); // Selected date without time

    if (!selectedDate.isValid()) {
        return { isValid: false, error: 'Invalid date selected.' };
    } else if (selectedDate.isAfter(today)) {
        return { isValid: false, error: `${fieldName} cannot be in the future.` };
    }

    return { isValid: true, error: null }; // Valid case
};

export const validatePincode = [
    (v) => !v || /^\d{6}$/.test(v) || "Invalid Pincode, it must be exactly 6 digits", // Ensures it is a 6-digit number
];

export const validateGSTIN = [
    (v) => !v || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(v) || "Invalid GSTIN number", // GSTIN format validation
];