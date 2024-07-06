/**
 * `isDark` 工具函数将十六进制颜色转换为 RGB，然后计算 YIQ 值以获取适当的亮度值。
 * 请参阅 https://www.w3.org/TR/AERT/#color-contrast 作为参考。
 *
 * 根据 W3C 标准，YIQ 值 >= 128 对应于浅色，但我们为每个亮色和暗色模式使用自定义阈值以保持设计一致性。
 */
export default function isDark(hexcolor: string | null): boolean {
  // 如果 hexcolor 是 undefined 或者长度小于 4 个字符（最短的十六进制形式为 #333），则返回 false；
  // 出于性能考虑，决定不使用正则表达式进行十六进制颜色验证
  if (!hexcolor || hexcolor.length < 4) {
    return false;
  }

  let hexnumbers = hexcolor.replace('#', '');

  if (hexnumbers.length === 3) {
    hexnumbers += hexnumbers;
  }

  const r = parseInt(hexnumbers.slice(0, 2), 16);
  const g = parseInt(hexnumbers.slice(2, 4), 16);
  const b = parseInt(hexnumbers.slice(4, 6), 16);
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;

  const threshold = parseInt(getComputedStyle(document.body).getPropertyValue('--yiq-threshold').trim()) || 128;

  return yiq < threshold;
}
