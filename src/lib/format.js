const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

/**
 * Converts Latin digits in a string to Persian digits, leaving everything else
 * untouched. Numbers rendered in Persian copy should always pass through here.
 *
 * @param {string|number} value
 * @returns {string}
 */
export function toPersianDigits(value) {
  return String(value).replace(/\d/g, (digit) => PERSIAN_DIGITS[Number(digit)]);
}

/**
 * Formats a number with thousands separators, then localises the digits.
 *
 * @param {number} value
 * @returns {string}
 */
export function formatNumber(value) {
  return toPersianDigits(value.toLocaleString('en-US'));
}
