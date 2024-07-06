import Component from '../Component';

import { createFocusTrap, FocusTrap } from '../utils/focusTrap';

import { disableBodyScroll, clearAllBodyScrollLocks } from 'body-scroll-lock';

import type ModalManagerState from '../states/ModalManagerState';
import type Mithril from 'mithril';

interface IModalManagerAttrs {
  state: ModalManagerState;
}

/**
 * `ModalManager` 组件管理一个或多个模态对话框。支持模态对话框的堆叠。可以同时显示多个对话框；将新组件加载到 ModalManager 中将覆盖前一个。
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
  // 当前焦点陷阱
  protected focusTrap: FocusTrap | undefined;

  // 跟踪最后设置的焦点陷阱
  protected lastSetFocusTrap: number | undefined;

  // 跟踪是否有模态对话框正在关闭
  protected modalClosing: boolean = false;

  protected keyUpListener: null | ((e: KeyboardEvent) => void) = null;

  view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children {
    return (
      <>
        {this.attrs.state.modalList.map((modal, i) => {
          const Tag = modal?.componentClass;

          return (
            <div
              key={modal.key}
              className="ModalManager modal"
              data-modal-key={modal.key}
              data-modal-number={i}
              role="dialog"
              aria-modal="true"
              style={{ '--modal-number': i }}
              aria-hidden={this.attrs.state.modal !== modal && 'true'}
            >
              {!!Tag && [
                <Tag
                  key={modal.key}
                  {...modal.attrs}
                  animateShow={this.animateShow.bind(this)}
                  animateHide={this.animateHide.bind(this)}
                  state={this.attrs.state}
                />,
                /* 这个背景是隐形的，用于点击外部区域来关闭模态对话框。 */
                <div key={modal.key} className="ModalManager-invisibleBackdrop" onclick={this.handlePossibleBackdropClick.bind(this)} />,
              ]}
            </div>
          );
        })}

        {this.attrs.state.backdropShown && (
          <div
            className="Modal-backdrop backdrop"
            ontransitionend={this.onBackdropTransitionEnd.bind(this)}
            data-showing={!!this.attrs.state.modalList.length}
            style={{ '--modal-count': this.attrs.state.modalList.length }}
          />
        )}
      </>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.oncreate(vnode);

    this.keyUpListener = this.handleEscPress.bind(this);
    document.body.addEventListener('keyup', this.keyUpListener);
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.onbeforeremove(vnode);

    this.keyUpListener && document.body.removeEventListener('keyup', this.keyUpListener);
    this.keyUpListener = null;
  }

  onupdate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void {
    super.onupdate(vnode);

    requestAnimationFrame(() => {
      try {
        // 当模态对话框显示或移除时，主内容应该获得或失去`aria-hidden`属性
        // 参见： http://web-accessibility.carnegiemuseums.org/code/dialogs/

        if (!this.attrs.state.isModalOpen()) {
          document.getElementById('app')?.setAttribute('aria-hidden', 'false');
          this.focusTrap!.deactivate?.();
          clearAllBodyScrollLocks();

          return;
        }

        document.getElementById('app')?.setAttribute('aria-hidden', 'true');

        // 获取当前对话框的键（可能是唯一标识符）
        const dialogKey = this.attrs.state.modal!.key;

        // 如果焦点陷阱已激活，并且上一次激活的焦点陷阱不是当前的对话框键，那么取消激活当前焦点陷阱
        if (this.focusTrap && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap!.deactivate?.();

          clearAllBodyScrollLocks();
        }

        // 如果有一个新的对话框尚未设置焦点陷阱，则激活焦点陷阱
        if (this.activeDialogElement && this.lastSetFocusTrap !== dialogKey) {
          this.focusTrap = createFocusTrap(this.activeDialogElement as HTMLElement, { allowOutsideClick: true });
          this.focusTrap!.activate?.();

          disableBodyScroll(this.activeDialogManagerElement!, { reserveScrollBarGap: true });
        }

        // 更新当前打开的模态对话框的键
        this.lastSetFocusTrap = dialogKey;
      } catch {
        // 由于mithril渲染的特性，我们可以预期这里会发生错误
      }
    });
  }

  /**
   * 获取当前活动的对话框元素
   */
  private get activeDialogElement(): HTMLElement {
    return document.body.querySelector(`.ModalManager[data-modal-key="${this.attrs.state.modal?.key}"] .Modal`) as HTMLElement;
  }

  /**
   * 获取当前活动的对话框元素
   */
  private get activeDialogManagerElement(): HTMLElement {
    return document.body.querySelector(`.ModalManager[data-modal-key="${this.attrs.state.modal?.key}"]`) as HTMLElement;
  }

  animateShow(readyCallback: () => void = () => {}): void {
    if (!this.attrs.state.modal) return;

    this.activeDialogElement.addEventListener(
      'transitionend',
      () => {
        readyCallback();
      },
      { once: true }
    );

    requestAnimationFrame(() => {
      this.activeDialogElement.classList.add('in');
    });
  }

  animateHide(closedCallback: () => void = () => {}): void {
    if (this.modalClosing) return;
    this.modalClosing = true;

    const afterModalClosedCallback = () => {
      this.modalClosing = false;

      // Close the dialog
      this.attrs.state.close();

      closedCallback();
    };

    this.activeDialogElement.addEventListener('transitionend', afterModalClosedCallback, { once: true });

    this.activeDialogElement.classList.remove('in');
    this.activeDialogElement.classList.add('out');
  }

  protected handleEscPress(e: KeyboardEvent): void {
    if (!this.attrs.state.modal) return;

    const dismissibleState = this.attrs.state.modal.componentClass.dismissibleOptions;

    // 如果按下了Esc键，则关闭对话框
    // 检查是否启用了通过Esc键关闭的功能
    if (e.key === 'Escape' && dismissibleState.viaEscKey) {
      e.preventDefault();

      this.animateHide();
    }
  }

  protected handlePossibleBackdropClick(e: MouseEvent): void {
    if (!this.attrs.state.modal || !this.attrs.state.modal.componentClass.dismissibleOptions.viaBackdropClick) return;

    this.animateHide();
  }

  protected onBackdropTransitionEnd(e: TransitionEvent) {
    if (e.propertyName === 'opacity') {
      const backdrop = e.currentTarget as HTMLDivElement;

      if (backdrop.getAttribute('data-showing') === null) {
        // 背景过渡结束时的处理函数
        this.attrs.state.backdropShown = false;
        m.redraw();
      }
    }
  }
}
