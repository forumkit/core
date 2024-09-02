import app from '../../forum/app';
import ComposerBody from './ComposerBody';
import extractText from '../../common/utils/extractText';
import Stream from '../../common/utils/Stream';

/**
 * `DiscussionComposer` 组件用于显示开始新讨论时的作曲家内容。
 * 它添加了一个文本字段作为头部控件，以便用户可以输入讨论标题。
 * 它还重写了 `submit` 和 `willExit` 操作来适应标题。
 *
 * ### Attrs  属性
 *
 * - ComposerBody 组件的所有属性
 * - `titlePlaceholder` ：标题输入框的占位符文本
 */
export default class DiscussionComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('core.forum.composer_discussion.body_placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.forum.composer_discussion.submit_button');
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('core.forum.composer_discussion.discard_confirmation'));
    attrs.titlePlaceholder = attrs.titlePlaceholder || extractText(app.translator.trans('core.forum.composer_discussion.title_placeholder'));
    attrs.className = 'ComposerBody--discussion';
  }

  oninit(vnode) {
    super.oninit(vnode);

    this.composer.fields.title = this.composer.fields.title || Stream('');

    /**
     * 标题输入框的值。
     *
     * @type {Function}
     */
    this.title = this.composer.fields.title;
  }

  headerItems() {
    const items = super.headerItems();

    items.add('title', <h3>{app.translator.trans('core.forum.composer_discussion.title')}</h3>, 100);

    items.add(
      'discussionTitle',
      <h3>
        <input
          className="FormControl"
          bidi={this.title}
          placeholder={this.attrs.titlePlaceholder}
          disabled={!!this.attrs.disabled}
          onkeydown={this.onkeydown.bind(this)}
        />
      </h3>
    );

    return items;
  }

  /**
   * 处理标题输入框的键盘按下事件。
   * 当按下回车键时，将焦点移动到文本编辑器的开始位置。
   *
   * @param {KeyboardEvent} e
   */
  onkeydown(e) {
    if (e.which === 13) {
      // 返回
      e.preventDefault();
      this.composer.editor.moveCursorTo(0);
    }

    e.redraw = false;
  }

  hasChanges() {
    return this.title() || this.composer.fields.content();
  }

  /**
   * 获取保存讨论时要提交到服务器的数据。
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      title: this.title(),
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    app.store
      .createRecord('discussions')
      .save(data)
      .then((discussion) => {
        this.composer.hide();
        app.discussions.refresh();
        m.route.set(app.route.discussion(discussion));
      }, this.loaded.bind(this));
  }
}
