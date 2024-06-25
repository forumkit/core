/**
 * 将字符串截断为指定长度，并在必要时追加省略号。
 */
export function truncate(string: string, length: number, start: number = 0): string {
  return (start > 0 ? '...' : '') + string.substring(start, start + length) + (string.length > start + length ? '...' : '');
}

/**
 * 根据所选模式，从给定字符串中创建一个slug。
 * 无效字符将被转换为连字符。
 *
 * 注意：此方法不使用后端采用的相对复杂的音译机制。因此，它应该只用于*建议*可以被用户覆盖的 slug。
 */
export function slug(string: string, mode: SluggingMode = SluggingMode.ALPHANUMERIC): string {
  switch (mode) {
    case SluggingMode.UTF8:
      return (
        string
          .toLowerCase()
          // 匹配非单词字符（考虑UTF8）并用连字符替换。
          .replace(/[^\p{L}\p{N}\p{M}]/giu, '-')
          .replace(/-+/g, '-')
          .replace(/-$|^-/g, '')
      );

    case SluggingMode.ALPHANUMERIC:
    default:
      return string
        .toLowerCase()
        .replace(/[^a-z0-9]/gi, '-')
        .replace(/-+/g, '-')
        .replace(/-$|^-/g, '');
  }
}

enum SluggingMode {
  ALPHANUMERIC = 'alphanum',
  UTF8 = 'utf8',
}

/**
 * 从给定字符串中删除HTML标签和引号，并用有意义的标点符号替换它们。
 */
export function getPlainContent(string: string): string {
  const html = string.replace(/(<\/p>|<br>)/g, '$1 &nbsp;').replace(/<img\b[^>]*>/gi, ' ');

  const element = new DOMParser().parseFromString(html, 'text/html').documentElement;

  getPlainContent.removeSelectors.forEach((selector) => {
    const el = element.querySelectorAll(selector);
    el.forEach((e) => {
      e.remove();
    });
  });

  return element.innerText.replace(/\s+/g, ' ').trim();
}

/**
 * 在获取纯文本时要删除的DOM选择器数组。
 *
 * @type {Array}
 */
getPlainContent.removeSelectors = ['blockquote', 'script'];

/**
 * 将字符串的第一个字符转换为大写。
 */
export function ucfirst(string: string): string {
  return string.substr(0, 1).toUpperCase() + string.substr(1);
}

/**
 * 将驼峰命名法的字符串转换为蛇形命名法。
 */
export function camelCaseToSnakeCase(str: string): string {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

/**
 * 生成指定长度的随机字符串（a-z，0-9）。
 *
 * 如果提供的长度小于0，则会引发错误。
 *
 * @param length 要生成的随机字符串的长度
 * @returns 指定长度的随机字符串 
 */
export function generateRandomString(length: number): string {
  if (length < 0) throw new Error('无法生成长度小于 0 的随机字符串。');
  if (length === 0) return '';

  const arr = new Uint8Array(length / 2);
  window.crypto.getRandomValues(arr);

  return Array.from(arr, (dec) => {
    return dec.toString(16).padStart(2, '0');
  }).join('');
}
