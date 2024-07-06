import app from '../../site/app';
import DiscussionPage from '../components/DiscussionPage';
import ReplyComposer from '../components/ReplyComposer';
import LogInModal from '../components/LogInModal';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import RenameDiscussionModal from '../components/RenameDiscussionModal';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';

/**
 * `DiscussionControls` 工具类用于构建一个针对讨论的按钮列表，这些按钮可以对讨论执行操作。
 */
export default {
  /**
   * 获取讨论的控件列表。
   *
   * @param {import('../../common/models/Discussion').default} discussion 讨论对象
   * @param {import('../../common/Component').default<any, any>} context 控件菜单将显示的父组件
   *
   * @return {ItemList<import('mithril').Children>} 的子元素列表
   */
  controls(discussion, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](discussion, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  /**
   * 获取与当前用户相关的讨论的控件（例如，回复、关注）。
   *
   * @param {import('../../common/models/Discussion').default} discussion 讨论对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>} 控件列表
   * @protected
   */
  userControls(discussion, context) {
    const items = new ItemList();

    // 如果这是讨论页面本身的讨论控件下拉菜单，才添加回复控件。
    // 我们不希望它在讨论列表等地方显示。
    if (context instanceof DiscussionPage) {
      items.add(
        'reply',
        !app.session.user || discussion.canReply() ? (
          <Button
            icon="fas fa-reply"
            onclick={() => {
              // 如果用户未登录，则promise会被拒绝，并显示登录模态框。
              // 因为这已经被处理了，所以我们不需要在控制台显示错误信息。
              return this.replyAction
                .bind(discussion)(true, false)
                .catch(() => {});
            }}
          >
            {app.translator.trans(
              app.session.user ? 'core.site.discussion_controls.reply_button' : 'core.site.discussion_controls.log_in_to_reply_button'
            )}
          </Button>
        ) : (
          <Button
            icon="fas fa-reply"
            className="disabled"
            title={extractText(app.translator.trans('core.site.discussion_controls.cannot_reply_text'))}
          >
            {app.translator.trans('core.site.discussion_controls.cannot_reply_button')}
          </Button>
        )
      );
    }

    return items;
  },

  /**
   * 获取与讨论相关的审核控件（例如，重命名、锁定）。
   *
   * @param {import('../../common/models/Discussion').default} discussion 讨论对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>} 控件列表
   * @protected
   */
  moderationControls(discussion) {
    const items = new ItemList();

    if (discussion.canRename()) {
      items.add(
        'rename',
        <Button icon="fas fa-pencil-alt" onclick={this.renameAction.bind(discussion)}>
          {app.translator.trans('core.site.discussion_controls.rename_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * 获取具有破坏性的讨论控件（例如，删除）。
   *
   * @param {import('../../common/models/Discussion').default} discussion 讨论对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>} 控件列表
   * @protected
   */
  destructiveControls(discussion) {
    const items = new ItemList();

    if (!discussion.isHidden()) {
      if (discussion.canHide()) {
        items.add(
          'hide',
          <Button icon="far fa-trash-alt" onclick={this.hideAction.bind(discussion)}>
            {app.translator.trans('core.site.discussion_controls.delete_button')}
          </Button>
        );
      }
    } else {
      if (discussion.canHide()) {
        items.add(
          'restore',
          <Button icon="fas fa-reply" onclick={this.restoreAction.bind(discussion)}>
            {app.translator.trans('core.site.discussion_controls.restore_button')}
          </Button>
        );
      }

      if (discussion.canDelete()) {
        items.add(
          'delete',
          <Button icon="fas fa-times" onclick={this.deleteAction.bind(discussion)}>
            {app.translator.trans('core.site.discussion_controls.delete_forever_button')}
          </Button>
        );
      }
    }

    return items;
  },

  /**
   * 打开讨论的回复编辑器。返回一个Promise，当编辑器成功打开时解析。如果用户未登录，将提示他们登录。如果他们没有回复的权限，Promise将被拒绝。
   *
   * @param {boolean} goToLast 如果正在查看讨论，是否滚动到最后一个帖子
   * @param {boolean} forceRefresh 否强制重新加载编辑器组件，即使它已经为这个讨论打开了
   *
   * @return {Promise<void>}
   */
  replyAction(goToLast, forceRefresh) {
    return new Promise((resolve, reject) => {
      if (app.session.user) {
        if (this.canReply()) {
          if (!app.composer.composingReplyTo(this) || forceRefresh) {
            app.composer.load(ReplyComposer, {
              user: app.session.user,
              discussion: this,
            });
          }
          app.composer.show();

          if (goToLast && app.viewingDiscussion(this) && !app.composer.isFullScreen()) {
            app.current.get('stream').goToNumber('reply');
          }

          return resolve(app.composer);
        } else {
          return reject();
        }
      }

      app.modal.show(LogInModal);

      return reject();
    });
  },

  /**
   * 隐藏讨论
   *
   * @return {Promise<void>}
   */
  hideAction() {
    this.pushData({ attributes: { hiddenAt: new Date() }, relationships: { hiddenUser: app.session.user } });

    return this.save({ isHidden: true });
  },

  /**
   * 恢复讨论
   *
   * @return {Promise<void>}
   */
  restoreAction() {
    this.pushData({ attributes: { hiddenAt: null }, relationships: { hiddenUser: null } });

    return this.save({ isHidden: false });
  },

  /**
   * 在与用户确认后删除讨论。
   *
   * @return {Promise<void>}
   */
  deleteAction() {
    if (confirm(extractText(app.translator.trans('core.site.discussion_controls.delete_confirmation')))) {
      // 如果用户确认删除
      // 如果当前正在查看被删除的讨论，则返回到上一页
      if (app.viewingDiscussion(this)) {
        app.history.back();
      }

      return this.delete().then(() => app.discussions.removeDiscussion(this));
    }
  },

  /**
   * 重命名讨论。
   */
  renameAction() {
    return app.modal.show(RenameDiscussionModal, {
      currentTitle: this.title(),
      discussion: this,
    });
  },
};
