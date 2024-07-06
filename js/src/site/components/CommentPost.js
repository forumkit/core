import app from '../../site/app';
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
 * `CommentPost` 组件用于显示标准的 `comment` 类型帖子。这包括帖子 HTML 内容周围的多个项目列表（控件、标题和页脚）。
 *
 * ### 属性 Attrs
 *
 * - `post`
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
     * PostUser 中的用户悬停卡片是否可见。
     * 为了在子树检查中使用此属性，必须在 CommentPost 中管理该属性
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

    // 如果帖子内容自上次渲染以来已更改，我们将遍历内容中的所有 <script> 标签并评估它们。
    // 这是必要的，因为 TextFormatter 会输出它们，例如用于语法高亮。
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
   * 切换隐藏帖子内容的可见性。
   */
  toggleContent() {
    this.revealContent = !this.revealContent;
  }

  /**
   * 为帖子的标题构建项目列表。
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

    // 如果帖子被隐藏，添加一个按钮，该按钮允许切换帖子内容的可见性。
    if (post.isHidden()) {
      items.add(
        'toggle',
        <Button className="Button Button--default Button--more" icon="fas fa-ellipsis-h" onclick={this.toggleContent.bind(this)} />
      );
    }

    return items;
  }
}
