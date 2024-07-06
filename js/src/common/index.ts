// 将 jQuery、mithril 和 dayjs 暴露给浏览器的 window 对象
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

// 在 Forumkit 的未来版本中将会移除。
// @ts-ignore
$.fn.tooltip = function (options, caller) {
  // 当 `$.tooltip` 在 Tooltip 组件外部被使用时显示警告。
  // 此功能已被弃用，不应再使用。 如果 caller 参数的值不在数组
  if (!['DANGEROUS_tooltip_jquery_fn_deprecation_exempt'].includes(caller)) {
    console.warn(
      "Calling `$.tooltip` is now deprecated. Please use the `<Tooltip>` component exposed by forumkit/core instead. `$.tooltip` may be removed in a future version of Forumkit.\n\nIf this component doesn't meet your requirements, please open an issue: "
    );
  }

  tooltipGen.bind(this)(options);
};
