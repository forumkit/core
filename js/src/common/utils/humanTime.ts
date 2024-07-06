// 导入中文本地化设置
import 'dayjs/locale/zh';
// 导入 dayjs 库
import dayjs from 'dayjs';
// 设置 dayjs 的语言为中文
dayjs.locale('zh');

/**
 * `humanTime` 实用程序将日期转换为本地化的、人类可读的时间前字符串。
 */
export default function humanTime(time: dayjs.ConfigType): string {
  let d = dayjs(time);
  const now = dayjs();

  // 为了防止因为客户端和服务器时间之间的微小偏差而显示诸如“在几秒钟内”之类的内容，
  // 我们总是将未来的日期重置为当前时间。这将导致显示“刚刚”而不是未来时间。
  if (d.isAfter(now)) {
    d = now;
  }

  const day = 864e5;
  const diff = d.diff(dayjs());
  let ago: string;

  // 如果这个日期是一个月前，我们将在字符串中显示月份的名称。
  // 如果不是今年，我们还将显示年份。
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
