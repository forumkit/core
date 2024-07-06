import humanTime from './humanTime';

function updateHumanTimes() {
  $('[data-humantime]').each(function () {
    const $this = $(this);
    const ago = humanTime($this.attr('datetime'));

    $this.html(ago);
  });
}

/**
 * `liveHumanTimes` 初始化器设置了一个每秒（实际是10秒，因为setInterval的第二个参数是10000毫秒）执行一次的循环，
 * 用于更新通过 `humanTime` 辅助函数渲染的时间戳。
 */
export default function liveHumanTimes() {
  setInterval(updateHumanTimes, 10000);
}
