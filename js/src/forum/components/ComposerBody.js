import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ConfirmDocumentUnload from '../../common/components/ConfirmDocumentUnload';
import TextEditor from '../../common/components/TextEditor';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';

/**
 * `ComposerBody` 组件负责处理作曲家的主体或内容。
 * 子类应该实现 `onsubmit` 方法，并可以根据需要重写 `headerItems` 方法。
 *
 * ### Attrs 属性
 *
 * - `composer` ：作曲家实例，用于管理作曲过程。
 * - `originalContent` ：原始内容，用于比较是否有更改。
 * - `submitLabel` ：提交按钮的标签。
 * - `placeholder` ：文本编辑器中的占位符文本。
 * - `user` ：当前用户对象，用于显示头像等。
 * - `confirmExit` ：在退出前需要确认的文本（如果有更改）。
 * - `disabled` ：是否禁用文本编辑器。
 *
 * @abstract
 */
export default class ComposerBody extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.composer = this.attrs.composer;

    /**
     * 表示组件是否正在加载。
     *
     * @type {Boolean}
     */
    this.loading = false;

    // 如果组件支持或需要在特定情况下请求退出确认，并且提供了相应的确认问题，
    // 则让作曲家状态知道在这些情况下请求确认。
    if (this.attrs.confirmExit) {
      this.composer.preventClosingWhen(() => this.hasChanges(), this.attrs.confirmExit);
    }

    this.composer.fields.content(this.attrs.originalContent || '');
  }

  view() {
    return (
      <ConfirmDocumentUnload when={this.hasChanges.bind(this)}>
        <div className={classList('ComposerBody', this.attrs.className)}>
          {avatar(this.attrs.user, { className: 'ComposerBody-avatar' })}
          <div className="ComposerBody-content">
            <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
            <div className="ComposerBody-editor">
              <TextEditor
                submitLabel={this.attrs.submitLabel}
                placeholder={this.attrs.placeholder}
                disabled={this.loading || this.attrs.disabled}
                composer={this.composer}
                preview={this.jumpToPreview?.bind(this)}
                onchange={this.composer.fields.content}
                onsubmit={this.onsubmit.bind(this)}
                value={this.composer.fields.content()}
              />
            </div>
          </div>
          <LoadingIndicator display="unset" containerClassName={classList('ComposerBody-loading', this.loading && 'active')} size="large" />
        </div>
      </ConfirmDocumentUnload>
    );
  }

  /**
   * 检查是否有未保存的数据。
   *
   * @return {boolean}
   */
  hasChanges() {
    const content = this.composer.fields.content();

    return content && content !== this.attrs.originalContent;
  }

  /**
   * 为作曲家的头部构建项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  headerItems() {
    return new ItemList();
  }

  /**
   * 处理文本编辑器的提交事件。
   *
   * @abstract
   */
  onsubmit() {}

  /**
   * 停止加载状态。
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
