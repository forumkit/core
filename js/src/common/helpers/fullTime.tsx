import dayjs from 'dayjs';
import type Mithril from 'mithril';

/**
 * `fullTime` 辅助函数将格式化的时间字符串包裹在 <time> 标签中并显示。
 */
export default function fullTime(time: Date): Mithril.Vnode {
  const d = dayjs(time);

  const datetime = d.format();
  const full = d.format('LLLL');

  return (
    <time pubdate datetime={datetime}>
      {full}
    </time>
  );
}
