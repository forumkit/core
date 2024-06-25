import dayjs from 'dayjs';
import type Mithril from 'mithril';
import humanTimeUtil from '../utils/humanTime';

/**
 * `humanTime` 辅助函数将时间以人类友好的时间前格式（例如 '12 天前'）显示，
 * 并将其包装在包含时间其他信息的 <time> 标签中。
 */
export default function humanTime(time: Date): Mithril.Vnode {
  const d = dayjs(time);

  const datetime = d.format();
  const full = d.format('LLLL');
  const ago = humanTimeUtil(time);

  return (
    <time pubdate datetime={datetime} title={full} data-humantime>
      {ago}
    </time>
  );
}
