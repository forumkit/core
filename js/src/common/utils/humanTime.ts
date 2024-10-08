// 导入中文本地化设置
import 'dayjs/locale/zh';
// 导入 dayjs 库
import dayjs from 'dayjs';
// 设置 dayjs 的语言为中文
dayjs.locale('zh');

/**
 * The `humanTime` utility converts a date to a localized, human-readable time-
 * ago string.
 */
export default function humanTime(time: dayjs.ConfigType): string {
  let d = dayjs(time);
  const now = dayjs();

  // To prevent showing things like "in a few seconds" due to small offsets
  // between client and server time, we always reset future dates to the
  // current time. This will result in "just now" being shown instead.
  if (d.isAfter(now)) {
    d = now;
  }

  const day = 864e5;
  const diff = d.diff(dayjs());
  let ago: string;

  // If this date was more than a month ago, we'll show the name of the month
  // in the string. If it wasn't this year, we'll show the year as well.
  if (diff < -30 * day) {
    if (d.year() === dayjs().year()) {
      ago = d.format('D MMM');
    } else {
      ago = d.format('ll');
    }
  } else {
    ago = d.fromNow();
  }

  return ago;
}
