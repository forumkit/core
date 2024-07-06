import Component from '../Component';

/**
 * `ConfirmDocumentUnload` 组件可以用于注册一个全局事件处理器，该处理器根据给定的回调属性（prop）的返回值来阻止关闭浏览器窗口/标签页。
 *
 * ### 属性（Attrs）
 *
 * - `when` - 一个回调函数，当浏览器在关闭窗口/标签页之前需要弹出确认对话框时返回 true
 */
export default class ConfirmDocumentUnload extends Component {
  handler() {
    return this.attrs.when() || undefined;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.boundHandler = this.handler.bind(this);
    $(window).on('beforeunload', this.boundHandler);
  }

  onremove(vnode) {
    super.onremove(vnode);

    $(window).off('beforeunload', this.boundHandler);
  }

  view(vnode) {
    return <>{vnode.children}</>;
  }
}
