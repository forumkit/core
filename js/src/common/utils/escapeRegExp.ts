const specialChars = /[.*+?^${}()|[\]\\]/g;

/**
 * 转义 input 中的 RegExp 特殊字符。
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions#escaping
 */
export default function escapeRegExp(input: string): string {
  return input.replace(specialChars, '\\$&');
}
