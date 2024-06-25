import Component from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ConfirmDocumentUnload from '../../common/components/ConfirmDocumentUnload';
import TextEditor from '../../common/components/TextEditor';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';

/**
 * `ComposerBody` 组件处理编辑器的主体或内容。子类应实现 `onsubmit` 方法并覆盖 `headerTimes`。
 *
 * ### 属性（Attrs）
 *
 * - `composer`
 * - `originalContent` 原始内容
 * - `submitLabel` 提交标签
 * - `placeholder` 占位符
 * - `user` 用户
 * - `confirmExit` 确认退出
 * - `disabled` 禁用
 *
 * @abstract
 */
export default class ComposerBody extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.composer = this.attrs.composer;

    /**
     * 组件是否正在加载。
     *
     * @type {Boolean}
     */
    this.loading = false;

    // 在某些情况下，如果主体支持或需要并有相应的确认问题要询问，则让编辑器状态知道要询问确认。
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
   * 检查是否有任何未保存的数据。
   *
   * @return {boolean}
   */
  hasChanges() {
    const content = this.composer.fields.content();

    return content && content !== this.attrs.originalContent;
  }

  /**
   * 为编辑器的标题栏构建项目列表。
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
   * 停止加载。
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
