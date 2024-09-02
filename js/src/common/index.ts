// 将jQuery、mithril和dayjs暴露给浏览器的window对象
import 'expose-loader?exposes=$,jQuery!jquery';
import 'expose-loader?exposes=m!mithril';
import 'expose-loader?exposes=dayjs!dayjs';

import 'bootstrap/js/affix';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';
import 'jquery.hotkeys/jquery.hotkeys';

import relativeTime from 'dayjs/plugin/relativeTime';
import localizedFormat from 'dayjs/plugin/localizedFormat';

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

import patchMithril from './utils/patchMithril';

patchMithril(window);

import app from './app';

export { app };

import './utils/arrayFlatPolyfill';

const tooltipGen = $.fn.tooltip;

// 在未来版本的Forumkit中将会移除。
// @ts-ignore
$.fn.tooltip = function (options, caller) {
  // 当在Tooltip组件外部使用 `$.tooltip` 时显示警告。
  // 此功能已被弃用，不应再使用。
  if (!['DANGEROUS_tooltip_jquery_fn_deprecation_exempt'].includes(caller)) {
    console.warn(
      "调用 `$.tooltip` 现已被弃用。请使用forumkit/core提供的 `<Tooltip>` 组件代替。在未来的Forumkit版本中， `$.tooltip` 可能会被移除。"
    );
  }

  tooltipGen.bind(this)(options);
};
