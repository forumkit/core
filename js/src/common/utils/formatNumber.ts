import app from '../../common/app';

/**
 * `formatNumber` 工具函数根据提供的地区信息将数字本地化为带有适当标点符号的字符串，
 * 否则将默认为用户的地区设置。
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 */
export default function formatNumber(number: number, locale: string = app.data.locale): string {
  return new Intl.NumberFormat(locale).format(number);
}
