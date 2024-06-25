import type Mithril from 'mithril';
import { truncate } from '../utils/string';

/**
 * `highlight` 辅助函数在字符串中搜索某个单词或短语，并用 <mark> 标签将匹配项包裹起来。
 *
 * @param string 要进行高亮显示的字符串
 * @param phrase 要进行高亮显示的单词或短语（可选）
 * @param [length] 字符串截断后的字符数（可选）
 *     字符串将在第一个匹配项周围进行截断
 */
export default function highlight(string: string, phrase?: string | RegExp, length?: number): Mithril.Vnode<any, any> | string {
  if (!phrase && !length) return string;

  // 如果短语不是正则表达式，则将其转换为全局正则表达式，以便我们可以在字符串中搜索匹配项。
  const regexp = phrase instanceof RegExp ? phrase : new RegExp(phrase ?? '', 'gi');

  let highlighted = string;
  let start = 0;

  // 如果提供了长度，则在第一个匹配项周围截断字符串。
  if (length) {
    if (phrase) start = Math.max(0, string.search(regexp) - length / 2);

    highlighted = truncate(highlighted, length, start);
  }

  // 将字符串转换为 HTML 实体，然后用标记突出显示所有匹配项<mark>。然后，我们将结果作为受信任的 HTML 字符串返回。
  highlighted = $('<div/>').text(highlighted).html();

  if (phrase) highlighted = highlighted.replace(regexp, '<mark>$&</mark>');

  return m.trust(highlighted);
}
