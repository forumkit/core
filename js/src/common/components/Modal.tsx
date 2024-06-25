import app from '../../common/app';
import Component from '../Component';
import Alert, { AlertAttrs } from './Alert';
import Button from './Button';

import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
import type RequestError from '../utils/RequestError';
import type ModalManager from './ModalManager';
import fireDebugWarning from '../helpers/fireDebugWarning';
import classList from '../utils/classList';

export interface IInternalModalAttrs {
  state: ModalManagerState;
  animateShow: ModalManager['animateShow'];
  animateHide: ModalManager['animateHide'];
}

export interface IDismissibleOptions {

  isDismissible: boolean;
  viaCloseButton: boolean;
  viaEscKey: boolean;
  viaBackdropClick: boolean;
}

/**
 * `Modal` 组件显示一个模态对话框，包裹在表单中。子类应该实现 `className`、`title` 和 `content` 方法。
 */
export default abstract class Modal<ModalAttrs extends IInternalModalAttrs = IInternalModalAttrs, CustomState = undefined> extends Component<
  ModalAttrs,
  CustomState
> {

  static readonly isDismissible: boolean = true;

  /**
   * 是否可以通过关闭按钮（X）关闭模态框？
   *
   * 如果为 `false`，则不显示关闭按钮。
   */
  protected static readonly isDismissibleViaCloseButton: boolean = true;
  /**
   * 是否可以通过键盘上的 Esc 键关闭模态框？
   */
  protected static readonly isDismissibleViaEscKey: boolean = true;
  /**
   * 是否可以通过点击背景关闭模态框？
   */
  protected static readonly isDismissibleViaBackdropClick: boolean = true;

  static get dismissibleOptions(): IDismissibleOptions {
    // 如果有人将 isDismissible 设置为 `false`，则提供与 Forumkit 早期版本相同的行为。
    if (!this.isDismissible) {
      return {
        isDismissible: false,
        viaCloseButton: false,
        viaEscKey: false,
        viaBackdropClick: false,
      };
    }

    return {
      isDismissible: true,
      viaCloseButton: this.isDismissibleViaCloseButton,
      viaEscKey: this.isDismissibleViaEscKey,
      viaBackdropClick: this.isDismissibleViaBackdropClick,
    };
  }

  protected loading: boolean = false;

  /**
   * 用于在标题下方显示警告组件的属性。
   */
  alertAttrs: AlertAttrs | null = null;

  oninit(vnode: Mithril.Vnode<ModalAttrs, this>) {
    super.oninit(vnode);

    const missingMethods: string[] = [];

    ['className', 'title', 'content', 'onsubmit'].forEach((method) => {
      if (!(this as any)[method]) {
        (this as any)[method] = function (): void {};
        missingMethods.push(method);
      }
    });

    if (missingMethods.length > 0) {
      fireDebugWarning(
        `Modal \`${this.constructor.name}\` does not implement all abstract methods of the Modal super class. Missing methods: ${missingMethods.join(
          ', '
        )}.`
      );
    }
  }

  oncreate(vnode: Mithril.VnodeDOM<ModalAttrs, this>) {
    super.oncreate(vnode);

    this.attrs.animateShow(() => this.onready());
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<ModalAttrs, this>): Promise<void> | void {
    super.onbeforeremove(vnode);

    // 如果当前全局模态状态不包含模态框，
    // 我们刚刚打开了一个新的模态框，因此，
    // 我们不需要显示隐藏动画。
    if (!this.attrs.state.modal) {
      // 在这里，我们确保动画有足够的时间完成。
      // 参见 https://mithril.js.org/lifecycle-methods.html#onbeforeremove
      // Bootstrap 的 Modal.TRANSITION_DURATION 是 300 毫秒。
      return new Promise((resolve) => setTimeout(resolve, 300));
    }
  }

  /**
   * @todo 在 2.0 版本中拆分为 FormModal 和 Modal
   */
  view() {
    if (this.alertAttrs) {
      this.alertAttrs.dismissible = false;
    }

    return (
      <div className={classList('Modal modal-dialog fade', this.className())}>
        <div className="Modal-content">
          {this.dismissibleOptions.viaCloseButton && (
            <div className="Modal-close App-backControl">
              <Button
                icon="fas fa-times"
                onclick={() => this.hide()}
                className="Button Button--icon Button--link"
                aria-label={app.translator.trans('core.lib.modal.close')}
              />
            </div>
          )}

          <form onsubmit={this.onsubmit.bind(this)}>
            <div className="Modal-header">
              <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
            </div>

            {!!this.alertAttrs && (
              <div className="Modal-alert">
                <Alert {...this.alertAttrs} />
              </div>
            )}

            {this.content()}
          </form>
        </div>
      </div>
    );
  }

  /**
   * 获取要应用于模态对话框的类名。
   */
  abstract className(): string;

  /**
   * 获取模态对话框的标题。
   */
  abstract title(): Mithril.Children;

  /**
   * 获取模态对话框的内容。
   */
  abstract content(): Mithril.Children;

  /**
   * 处理模态表单的提交事件。
   */
  onsubmit(e: SubmitEvent): void {
    // ...
  }

  /**
   * 当模态对话框显示并准备好进行交互时执行的回调函数。
   *
   * @remark 将焦点设置到模态对话框中的第一个输入框
   */
  onready(): void {
    this.$().find('input, select, textarea').first().trigger('focus').trigger('select');
  }

  /**
   * 隐藏模态对话框
   */
  hide(): void {
    this.attrs.animateHide();
  }

  /**
   * 将 `loading` 设置为 false 并触发重新绘制
   */
  loaded(): void {
    this.loading = false;
    m.redraw();
  }

  /**
   * 显示描述从 API 返回的错误的警告，并将焦点设置到与错误相关的第一个字段
   */
  onerror(error: RequestError): void {
    this.alertAttrs = error.alert;

    m.redraw();

    if (error.status === 422 && error.response?.errors) {
      this.$('form [name=' + (error.response.errors as any[])[0].source.pointer.replace('/data/attributes/', '') + ']').trigger('select');
    } else {
      this.onready();
    }
  }

  private get dismissibleOptions(): IDismissibleOptions {
    return (this.constructor as typeof Modal).dismissibleOptions;
  }
}
