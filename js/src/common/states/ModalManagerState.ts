import type Component from '../Component';
import Modal, { IDismissibleOptions } from '../components/Modal';

/**
 * 理想情况下， `show` 方法应该接受一个高阶泛型，类似于：
 *  `show<Attrs, C>(componentClass: C<Attrs>, attrs: Attrs): void`
 * 遗憾的是，TypeScript 不支持这一特性：
 * https://github.com/Microsoft/TypeScript/issues/1213
 * 因此，我们不得不使用这种丑陋且混乱的替代方案。
 */
type UnsafeModalClass = ComponentClass<any, Modal> & { get dismissibleOptions(): IDismissibleOptions; component: typeof Component.component };

type ModalItem = {
  componentClass: UnsafeModalClass;
  attrs?: Record<string, unknown>;
  key: number;
};

/**
 * 用于管理模态对话框状态的类。
 *
 * 可以通过 `app` 属性在 `app.modal` 对象上访问。
 */
export default class ModalManagerState {
  /**
   * 当前显示的模态对话框
   * 
   * @internal
   */
  modal: ModalItem | null = null;

  /**
   * 模态对话框列表
   * 
   * @internal
   */
  modalList: ModalItem[] = [];

  /**
   * 背景是否显示
   * 
   * @internal
   */
  backdropShown: boolean = false;

  /**
   * 如果一个模态对话框被同类型的另一个替换，则用于强制重新初始化模态对话框。
   */
  private key = 0;

  /**
   * 显示一个模态对话框。
   *
   * 如果 `stackModal` 为 `true`，则模态对话框将显示在当前模态对话框之上。
   *
   * 如果未提供 `stackModal` 的值，则为了向后兼容，打开新模态对话框时会关闭任何其他当前显示的模态对话框。
   *
   * @example <caption>显示一个模态对话框</caption>
   * app.modal.show(MyCoolModal, { attr: 'value' });
   *
   * @example <caption>在生命周期方法（如 `oncreate`、`view` 等）中显示模态对话框</caption>
   * // 由于 Mithril 中嵌套重绘的怪癖，需要这种 "hack"
   * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
   *
   * @example <caption>堆叠模态对话框</caption>
   * app.modal.show(MyCoolStackedModal, { attr: 'value' }, true);
   */
  show(componentClass: UnsafeModalClass, attrs: Record<string, unknown> = {}, stackModal: boolean = false): void {
    if (!(componentClass.prototype instanceof Modal)) {
      // 重复这个错误检查，以便在捕获到错误时，调试控制台中仍然显示错误消息。
      const invalidModalWarning = 'ModalManager 只能显示 Modals。';
      console.error(invalidModalWarning);
      throw new Error(invalidModalWarning);
    }

    this.backdropShown = true;
    m.redraw.sync();

    // 在这里使用 requestAnimationFrame，因为我们需要在将模态对话框添加到模态对话框列表之前等待背景被添加到 DOM。
    //
    // 这是因为我们在 ModalManager 的 onupdate 生命周期钩子内部使用了 RAF，如果我们跳过这个 RAF 调用，
    // 钩子将尝试在模态对话框尚未在 DOM 中时为其添加焦点陷阱并锁定滚动，从而导致出现一个额外的滚动条。
    requestAnimationFrame(() => {
      // 设置当前模态对话框
      this.modal = { componentClass, attrs, key: this.key++ };

      // 我们想要堆叠这个模态对话框
      if (stackModal) {
        // 记住之前打开的模态对话框并将新模态对话框添加到模态对话框列表中
        this.modalList.push(this.modal);
      } else {
        // 覆盖最后一个模态对话框
        this.modalList = [this.modal];
      }

      m.redraw();
    });
  }

  /**
   * 如果当前有打开的对话框，则关闭最顶部的对话框。
   */
  close(): void {
    if (!this.modal) return;

    // 如果有两个模态框，则移除最近的一个
    if (this.modalList.length > 1) {
      // 从列表中移除最后一个模态框
      this.modalList.pop();

      // 打开列表中的最后一个模态框
      this.modal = this.modalList[this.modalList.length - 1];
    } else {
      // 重置状态
      this.modal = null;
      this.modalList = [];
    }

    m.redraw();
  }

  /**
   * 检查是否当前有模态框打开。
   *
   * @return 如果当前有模态框打开则返回  `true`，否则返回 `false`。
   */
  isModalOpen(): boolean {
    return !!this.modal;
  }
}
