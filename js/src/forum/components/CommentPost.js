import app from '../../forum/app';
import Post from './Post';
import classList from '../../common/utils/classList';
import PostUser from './PostUser';
import PostMeta from './PostMeta';
import PostEdited from './PostEdited';
import EditPostComposer from './EditPostComposer';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import ComposerPostPreview from './ComposerPostPreview';

/**
 * `CommentPost` 组件用于显示标准类型为 `comment` 的帖子。
 * 它包含多个项目列表（控件、头部和尾部），这些列表围绕着帖子的 HTML 内容。
 *
 * ### Attrs 属性
 *
 * - `post` ：帖子对象
 */
export default class CommentPost extends Post {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 如果帖子已被隐藏，则此标志确定其内容是否已展开。
     *
     * @type {Boolean}
     */
    this.revealContent = false;

    /**
     * 确定 PostUser 内的用户悬停卡片是否可见。
     * 此属性必须在 CommentPost 中管理，以便在子树检查中使用它。
     *
     * @type {Boolean}
     */
    this.cardVisible = false;

    this.subtree.check(
      () => this.cardVisible,
      () => this.isEditing(),
      () => this.revealContent
    );
  }

  content() {
    return super.content().concat([
      <header className="Post-header">
        <ul>{listItems(this.headerItems().toArray())}</ul>
      </header>,
      <div className="Post-body">
        {this.isEditing() ? <ComposerPostPreview className="Post-preview" composer={app.composer} /> : m.trust(this.attrs.post.contentHtml())}
      </div>,
    ]);
  }

  refreshContent() {
    const contentHtml = this.isEditing() ? '' : this.attrs.post.contentHtml();

    // 如果内容自上次渲染以来已更改，则执行脚本标签的替换操作
    // 这对于执行TextFormatter输出的脚本（如语法高亮）是必要的
    if (this.contentHtml !== contentHtml) {
      this.$('.Post-body script').each(function () {
        const script = document.createElement('script');
        script.textContent = this.textContent;
        Array.from(this.attributes).forEach((attr) => script.setAttribute(attr.name, attr.value));
        this.parentNode.replaceChild(script, this);
      });
    }

    this.contentHtml = contentHtml;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.refreshContent();
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    this.refreshContent();
  }

  isEditing() {
    return app.composer.bodyMatches(EditPostComposer, { post: this.attrs.post });
  }

  elementAttrs() {
    const post = this.attrs.post;
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className, 'CommentPost', {
      'Post--renderFailed': post.renderFailed(),
      'Post--hidden': post.isHidden(),
      'Post--edited': post.isEdited(),
      revealContent: this.revealContent,
      editing: this.isEditing(),
    });

    if (this.isEditing()) attrs['aria-busy'] = 'true';

    return attrs;
  }

  /**
   * 切换隐藏帖子的内容可见性。
   */
  toggleContent() {
    this.revealContent = !this.revealContent;
  }

  /**
   * 为文章的标题构建一个项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  headerItems() {
    const items = new ItemList();
    const post = this.attrs.post;

    items.add(
      'user',
      <PostUser
        post={post}
        cardVisible={this.cardVisible}
        oncardshow={() => {
          this.cardVisible = true;
          m.redraw();
        }}
        oncardhide={() => {
          this.cardVisible = false;
          m.redraw();
        }}
      />,
      100
    );
    items.add('meta', <PostMeta post={post} />);

    if (post.isEdited() && !post.isHidden()) {
      items.add('edited', <PostEdited post={post} />);
    }

    // 如果帖子是隐藏的，添加一个按钮，允许切换帖子内容的可见性。
    if (post.isHidden()) {
      items.add(
        'toggle',
        <Button className="Button Button--default Button--more" icon="fas fa-ellipsis-h" onclick={this.toggleContent.bind(this)} />
      );
    }

    return items;
  }
}
