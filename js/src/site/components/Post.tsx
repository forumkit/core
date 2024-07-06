import app from '../../site/app';
import Component, { ComponentAttrs } from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import Dropdown from '../../common/components/Dropdown';
import PostControls from '../utils/PostControls';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import type PostModel from '../../common/models/Post';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import type Mithril from 'mithril';

export interface IPostAttrs extends ComponentAttrs {
  post: PostModel;
}

/**
 * `Post` 组件用于展示单篇帖子。基本的帖子模板只包含一个控件下拉菜单；子类必须实现 `content` 和 `attrs` 方法。
 */
export default abstract class Post<CustomAttrs extends IPostAttrs = IPostAttrs> extends Component<CustomAttrs> {
  /**
   * 可由子类设置。
   */
  loading = false;

  /**
   * 确保帖子不会被重新绘制，
   * 除非有新的数据传入。
   */
  subtree!: SubtreeRetainer;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.loading = false;

    this.subtree = new SubtreeRetainer(
      () => this.loading,
      () => this.attrs.post.freshness,
      () => {
        const user = this.attrs.post.user();
        return user && user.freshness;
      }
    );
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const attrs = this.elementAttrs();

    attrs.className = this.classes(attrs.className as string | undefined).join(' ');

    const controls = PostControls.controls(this.attrs.post, this).toArray();
    const footerItems = this.footerItems().toArray();

    return (
      <article {...attrs}>
        <div>
          {this.loading ? <LoadingIndicator /> : this.content()}
          <aside className="Post-actions">
            <ul>
              {listItems(this.actionItems().toArray())}
              {!!controls.length && (
                <li>
                  <Dropdown
                    className="Post-controls"
                    buttonClassName="Button Button--icon Button--flat"
                    menuClassName="Dropdown-menu--right"
                    icon="fas fa-ellipsis-h"
                    onshow={() => this.$('.Post-controls').addClass('open')}
                    onhide={() => this.$('.Post-controls').removeClass('open')}
                    accessibleToggleLabel={app.translator.trans('core.site.post_controls.toggle_dropdown_accessible_label')}
                  >
                    {controls}
                  </Dropdown>
                </li>
              )}
            </ul>
          </aside>
          <footer className="Post-footer">{footerItems.length ? <ul>{listItems(footerItems)}</ul> : null}</footer>
        </div>
      </article>
    );
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeupdate(vnode);

    return this.subtree.needsRebuild();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    const $actions = this.$('.Post-actions');
    const $controls = this.$('.Post-controls');

    $actions.toggleClass('openWithin', $controls.hasClass('open'));
  }

  /**
   * 获取帖子元素的属性。
   */
  elementAttrs(): Record<string, unknown> {
    return {};
  }

  /**
   * 获取帖子的内容。
   */
  content(): Mithril.Children {

    return [];
  }

  /**
   * 获取帖子的类名列表。
   */
  classes(existing?: string): string[] {
    let classes = (existing || '').split(' ').concat(['Post']);

    const user = this.attrs.post.user();
    const discussion = this.attrs.post.discussion();

    if (this.loading) {
      classes.push('Post--loading');
    }

    if (user && user === app.session.user) {
      classes.push('Post--by-actor');
    }

    if (user && user === discussion.user()) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  /**
   * 构建帖子操作的项列表。
   */
  actionItems(): ItemList<Mithril.Children> {
    return new ItemList();
  }

  /**
   * 构建帖子页脚的项列表。
   */
  footerItems(): ItemList<Mithril.Children> {
    return new ItemList();
  }
}
