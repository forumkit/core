type RGB = { r: number; g: number; b: number };

function hsvToRgb(h: number, s: number, v: number): RGB {
  let r!: number;
  let g!: number;
  let b!: number;

  const i = Math.floor(h * 6);
  const f = h * 6 - i;
  const p = v * (1 - s);
  const q = v * (1 - f * s);
  const t = v * (1 - (1 - f) * s);

  switch (i % 6) {
    case 0:
      r = v;
      g = t;
      b = p;
      break;
    case 1:
      r = q;
      g = v;
      b = p;
      break;
    case 2:
      r = p;
      g = v;
      b = t;
      break;
    case 3:
      r = p;
      g = q;
      b = v;
      break;
    case 4:
      r = t;
      g = p;
      b = v;
      break;
    case 5:
      r = v;
      g = p;
      b = q;
      break;
  }

  return {
    r: Math.floor(r * 255),
    g: Math.floor(g * 255),
    b: Math.floor(b * 255),
  };
}

/**
 * 将给定的字符串转换为唯一的颜色。
 */
export default function stringToColor(string: string): string {
  let num = 0;

  // 根据每个字符的 ASCII 值将用户名转换为一个数字。
  for (let i = 0; i < string.length; i++) {
    num += string.charCodeAt(i);
  }

  // 使用该数字除以 360 的余数以及一些预定义的饱和度和亮度值来构造颜色。
  const hue = num % 360;
  const rgb = hsvToRgb(hue / 360, 0.3, 0.9);

  return '' + rgb.r.toString(16) + rgb.g.toString(16) + rgb.b.toString(16);
}
