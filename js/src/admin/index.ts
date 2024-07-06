import app from './app';

export { app };

// 导出公共API

// 导出兼容API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-expect-error 这里有一个TypeScript期望的错误，因为 `app` 实例需要在兼容性对象中可用
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'admin');
