import app from '../../common/app';
import extractText from './extractText';

/**
 * `abbreviateNumber` 辅助函数将一个数字转换为较短的本地化形式。
 *
 * @example
 * abbreviateNumber(1234);
 * // "1.2K"
 */
export default function abbreviateNumber(number: number): string {
  // TODO: 翻译
  if (number >= 1000000) {
    return Math.floor(number / 1000000) + extractText(app.translator.trans('core.lib.number_suffix.mega_text'));
  } else if (number >= 1000) {
    return (number / 1000).toFixed(1) + extractText(app.translator.trans('core.lib.number_suffix.kilo_text'));
  } else {
    return number.toString();
  }
}
