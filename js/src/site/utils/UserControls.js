import app from '../../site/app';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import EditUserModal from '../../common/components/EditUserModal';
import UserPage from '../components/UserPage';
import ItemList from '../../common/utils/ItemList';

/**
 * `UserControls` 工具类用于为用户构建一组按钮，这些按钮用于对用户执行操作。
 */
export default {
  /**
   * 获取一个用户的控制列表。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   * @param {import('../../common/Component').default<any, any>}  context 上下文，表示将显示控件菜单的父组件。
   *
   * @return {ItemList<import('mithril').Children>} 包含mithril子组件的ItemList对象
   */
  controls(user, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](user, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  /**
   * 获取与用户当前状态相关的控制项（例如：戳一戳、关注）。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  userControls() {
    return new ItemList();
  },

  /**
   * 获取与用户管理相关的控制项（例如：暂停、编辑）。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  moderationControls(user) {
    const items = new ItemList();

    if (user.canEdit() || user.canEditCredentials() || user.canEditGroups()) {
      items.add(
        'edit',
        <Button icon="fas fa-pencil-alt" onclick={this.editAction.bind(this, user)}>
          {app.translator.trans('core.site.user_controls.edit_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * 获取与用户相关的破坏性控制项（例如：删除）。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   * @param {import('../../common/Component').default<any, any>}  context 控件菜单将显示在其下的父组件
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  destructiveControls(user) {
    const items = new ItemList();

    if (user.id() !== '1' && user.canDelete()) {
      items.add(
        'delete',
        <Button icon="fas fa-times" onclick={this.deleteAction.bind(this, user)}>
          {app.translator.trans('core.site.user_controls.delete_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * 删除用户。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   */
  deleteAction(user) {
    if (!confirm(app.translator.trans('core.site.user_controls.delete_confirmation'))) {
      return;
    }

    user
      .delete()
      .then(() => {
        this.showDeletionAlert(user, 'success');
        if (app.current.matches(UserPage, { user })) {
          app.history.back();
        } else {
          window.location.reload();
        }
      })
      .catch(() => this.showDeletionAlert(user, 'error'));
  },

  /**
   * 显示用户删除提示。
   *
   * @param {import('../../common/models/User').default} user 用户对象
   * @param {string} type
   */
  showDeletionAlert(user, type) {
    const message = {
      success: 'core.site.user_controls.delete_success_message',
      error: 'core.site.user_controls.delete_error_message',
    }[type];

    app.alerts.show(
      { type },
      app.translator.trans(message, {
        user,
        email: user.email(),
      })
    );
  },

  /**
   * 编辑用户。
   *
   * @param {import('../../common/models/User').default} user 用户对象 
   */
  editAction(user) {
    app.modal.show(EditUserModal, { user });
  },
};
