export interface EditorDriverParams {
  /**
   * 应用于编辑器主 DOM 元素的 HTML 类名数组。
   */
  classNames: string[];

  /**
   * 编辑器是否应初始化为禁用状态。
   */
  disabled: boolean;

  /**
   * 编辑器的可选占位符。
   */
  placeholder: string;

  /**
   * 编辑器的可选初始值。
   */
  value: string;

  /**
   * 这与 inputListeners 分开，因为它会接收完整的序列化内容。
   * 它被视为私有 API，不应由未实现 EditorDriverInterface 的扩展程序使用/修改。
   */
  oninput: Function;

  /**
   * 点击、输入和键盘释放时，将调用这些函数中的每一个。
   * 不传递任何参数。
   */
  inputListeners: Function[];

  /**
   * 如果通过键盘绑定以编程方式触发提交，则将调用此函数。
   * 不应传递任何参数。
   */
  onsubmit: Function;
}

export default interface EditorDriverInterface {
  /**
   * 将焦点放在编辑器上，并将光标放置在给定位置。
   */
  moveCursorTo(position: number): void;

  /**
   * 获取编辑器的选定范围。
   */
  getSelectionRange(): Array<number>;

  /**
   * 从当前的 "text block" 中获取最后 N 个字符。
   *
   * 基于文本区域的驱动程序将只返回最后 N 个字符，
   * 但更高级的实现可能仅限于当前块。
   *
   * 这对于监控用户最近的输入以触发自动完成很有用。
   */
  getLastNChars(n: number): string;

  /**
   * 在光标位置插入内容到编辑器中。
   */
  insertAtCursor(text: string, escape: boolean): void;

  /**
   * 在给定位置插入内容到编辑器中。
   */
  insertAt(pos: number, text: string, escape: boolean): void;

  /**
   * 在给定位置之间插入内容到编辑器中。
   *
   * 如果开始和结束位置不同，则它们之间的任何文本都将被覆盖。
   */
  insertBetween(start: number, end: number, text: string, escape: boolean): void;

  /**
   * 从开始位置到当前光标位置替换现有内容。
   */
  replaceBeforeCursor(start: number, text: string, escape: boolean): void;

  /**
   * 获取相对于编辑器视口的插入符的左侧和顶部坐标。
   */
  getCaretCoordinates(position: number): { left: number; top: number };

  /**
   * 设置编辑器的禁用状态。
   */
  disabled(disabled: boolean): void;

  /**
   * 聚焦到编辑器。
   */
  focus(): void;

  /**
   * 销毁编辑器
   */
  destroy(): void;
}
