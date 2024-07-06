type KeyboardEventHandler = (event: KeyboardEvent) => void;
type ShouldHandle = (event: KeyboardEvent) => boolean;

enum Keys {
  Enter = 13,
  Escape = 27,
  Space = 32,
  ArrowUp = 38,
  ArrowDown = 40,
  ArrowLeft = 37,
  ArrowRight = 39,
  Tab = 9,
  Backspace = 8,
}

/**
 * `KeyboardNavigatable` 类管理可以通过键盘导航的列表，并为每个操作调用回调函数。
 *
 * 这个辅助类封装了键盘绑定逻辑，提供了一个简单的流畅API供使用。
 */
export default class KeyboardNavigatable {
  /**
   * 为特定输入执行的回调函数。
   */
  protected callbacks = new Map<number, KeyboardEventHandler>();

  /**
   * 确定是否应该处理键盘输入的回调函数。默认情况下，总是处理键盘导航。
   */
  protected whenCallback: ShouldHandle = (event: KeyboardEvent) => true;

  /**
   * 当向上导航时执行的回调函数。
   *
   * 这将由向上键触发。
   */
  onUp(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.ArrowUp, (e) => {
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * 当向下导航时执行的回调函数。
   *
   * 这将由向下键触发。
   */
  onDown(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.ArrowDown, (e) => {
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * 当前项被选中时执行的回调函数。
   *
   * 这将由回车键（如果不禁用）和Tab键触发。
   */
  onSelect(callback: KeyboardEventHandler, ignoreTabPress: boolean = false): KeyboardNavigatable {
    const handler: KeyboardEventHandler = (e) => {
      e.preventDefault();
      callback(e);
    };

    if (!ignoreTabPress) this.callbacks.set(Keys.Tab, handler);
    this.callbacks.set(Keys.Enter, handler);

    return this;
  }

  /**
   * 当前项通过Tab键被选中时执行的回调函数。
   *
   * 这将由Tab键触发。
   */
  onTab(callback: KeyboardEventHandler): KeyboardNavigatable {
    const handler: KeyboardEventHandler = (e) => {
      e.preventDefault();
      callback(e);
    };

    this.callbacks.set(9, handler);

    return this;
  }

  /**
   * 当导航被取消时执行的回调函数。
   *
   * 这将由Escape键触发。
   */
  onCancel(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.Escape, (e) => {
      e.stopPropagation();
      e.preventDefault();
      callback(e);
    });

    return this;
  }

  /**
   * 当移除前一个输入时执行的回调函数。
   *
   * 这将由Backspace键触发。
   */
  onRemove(callback: KeyboardEventHandler): KeyboardNavigatable {
    this.callbacks.set(Keys.Backspace, (e) => {
      if (e instanceof KeyboardEvent && e.target instanceof HTMLInputElement && e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
        callback(e);
        e.preventDefault();
      }
    });

    return this;
  }

  /**
   * 提供一个回调函数来确定是否应该处理键盘输入。
   */
  when(callback: ShouldHandle): KeyboardNavigatable {
    this.whenCallback = callback;

    return this;
  }

  /**
   * 在给定的jQuery元素上设置导航键绑定。
   */
  bindTo($element: JQuery<HTMLElement>) {
    // 在可导航元素上处理导航键事件。
    $element[0].addEventListener('keydown', this.navigate.bind(this));
  }

  /**
   * 将给定的键盘事件解释为导航命令。
   */
  navigate(event: KeyboardEvent) {
    // 这个回调函数决定了是否应该处理键盘事件或忽略它。
    if (!this.whenCallback(event)) return;

    const keyCallback = this.callbacks.get(event.which);
    if (keyCallback) {
      keyCallback(event);
    }
  }
}
