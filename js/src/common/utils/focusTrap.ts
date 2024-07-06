import { createFocusTrap as _createFocusTrap } from 'focus-trap';

/**
 * 为给定的元素使用给定的选项创建一个焦点陷阱。
 * 
 * 此函数应用了一些与库不同的默认选项。
 * 您自己的选项仍然会覆盖这些自定义的默认值：
 * 
 * ```json
 * {
     escapeDeactivates: false,
 * }
 * ```
 * 
 * @param element 要成为焦点陷阱的元素，或者用于查找元素的选择器
 * 
 * @see https://github.com/focus-trap/focus-trap#readme - 库文档
 */
function createFocusTrap(...args: Parameters<typeof _createFocusTrap>): ReturnType<typeof _createFocusTrap> {
  args[1] = {
    escapeDeactivates: false,
    ...args[1],
  };

  return _createFocusTrap(...args);
}

export * from 'focus-trap';
export { createFocusTrap };
