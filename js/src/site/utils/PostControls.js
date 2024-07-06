import app from '../../site/app';
import EditPostComposer from '../components/EditPostComposer';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';

/**
 * `PostControls` 工具用于为一个帖子构建一组按钮列表，这些按钮用于对帖子执行操作。
 */
export default {
  /**
   * 获取帖子的控制列表。
   *
   * @param {import('../../common/models/Post').default} post 帖子对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}')} 控件列表
   */
  controls(post, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](post, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  /**
   * 获取与当前用户相关的帖子控制项（例如：举报）。
   *
   * @param {import('../../common/models/Post').default} post 帖子对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}')} 控件列表
   * @protected
   */
  userControls(post, context) {
    return new ItemList();
  },

  /**
   * 获取与帖子管理相关的控制项（例如：编辑）。
   *
   * @param {import('../../common/models/Post').default} post 帖子对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}')} 控件列表
   * @protected
   */
  moderationControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && post.canEdit()) {
      if (!post.isHidden()) {
        items.add(
          'edit',
          <Button icon="fas fa-pencil-alt" onclick={this.editAction.bind(post)}>
            {app.translator.trans('core.site.post_controls.edit_button')}
          </Button>
        );
      }
    }

    return items;
  },

  /**
   * 获取具有破坏性的帖子控制项（例如：删除）。
   *
   * @param {import('../../common/models/Post').default} post 帖子对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}')} 控件列表
   * @protected
   */
  destructiveControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && !post.isHidden()) {
      if (post.canHide()) {
        items.add(
          'hide',
          <Button icon="far fa-trash-alt" onclick={this.hideAction.bind(post)}>
            {app.translator.trans('core.site.post_controls.delete_button')}
          </Button>
        );
      }
    } else {
      if (post.contentType() === 'comment' && post.canHide()) {
        items.add(
          'restore',
          <Button icon="fas fa-reply" onclick={this.restoreAction.bind(post)}>
            {app.translator.trans('core.site.post_controls.restore_button')}
          </Button>
        );
      }
      if (post.canDelete()) {
        items.add(
          'delete',
          <Button icon="fas fa-times" onclick={this.deleteAction.bind(post, context)}>
            {app.translator.trans('core.site.post_controls.delete_forever_button')}
          </Button>
        );
      }
    }

    return items;
  },

  /**
   * 打开编辑器以编辑帖子
   *
   * @return {Promise<void>}
   */
  editAction() {
    return new Promise((resolve) => {
      app.composer.load(EditPostComposer, { post: this });
      app.composer.show();

      return resolve();
    });
  },

  /**
   * 隐藏帖子
   *
   * @return {Promise<void>}
   */
  hideAction() {
    if (!confirm(extractText(app.translator.trans('core.site.post_controls.hide_confirmation')))) return;
    this.pushData({ attributes: { hiddenAt: new Date() }, relationships: { hiddenUser: app.session.user } });

    return this.save({ isHidden: true }).then(() => m.redraw());
  },

  /**
   * 恢复帖子
   *
   * @return {Promise<void>}
   */
  restoreAction() {
    this.pushData({ attributes: { hiddenAt: null }, relationships: { hiddenUser: null } });

    return this.save({ isHidden: false }).then(() => m.redraw());
  },

  /**
   * 删除帖子
   *
   * @return {Promise<void>}
   */
  deleteAction(context) {
    if (!confirm(extractText(app.translator.trans('core.site.post_controls.delete_confirmation')))) return;
    if (context) context.loading = true;

    return this.delete()
      .then(() => {
        const discussion = this.discussion();

        discussion.removePost(this.id());

        // 如果该讨论中没有帖子了，我们假设整个讨论也被删除了
        if (!discussion.postIds().length) {
          app.discussions.removeDiscussion(discussion);

          if (app.viewingDiscussion(discussion)) {
            app.history.back();
          }
        }
      })
      .catch(() => {})
      .then(() => {
        if (context) context.loading = false;
        m.redraw();
      });
  },
};
