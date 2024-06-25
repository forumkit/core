// 将 punycode 和 ColorThief 公开给窗口浏览器对象
import 'expose-loader?exposes=punycode!punycode';
import 'expose-loader?exposes=ColorThief!color-thief-browser';

import app from './app';

export { app };

// 导出兼容 API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-ignore
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'site');
