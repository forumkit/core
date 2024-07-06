import ColorThief, { Color } from 'color-thief-browser';

import Model from '../Model';
import stringToColor from '../utils/stringToColor';
import ItemList from '../utils/ItemList';
import computed from '../utils/computed';
import GroupBadge from '../components/GroupBadge';
import Mithril from 'mithril';
import Group from './Group';

export default class User extends Model {
  username() {
    return Model.attribute<string>('username').call(this);
  }
  slug() {
    return Model.attribute<string>('slug').call(this);
  }
  displayName() {
    return Model.attribute<string>('displayName').call(this);
  }

  email() {
    return Model.attribute<string | undefined>('email').call(this);
  }
  isEmailConfirmed() {
    return Model.attribute<boolean | undefined>('isEmailConfirmed').call(this);
  }

  password() {
    return Model.attribute<string | undefined>('password').call(this);
  }

  avatarUrl() {
    return Model.attribute<string | null>('avatarUrl').call(this);
  }

  preferences() {
    return Model.attribute<Record<string, any> | null | undefined>('preferences').call(this);
  }

  groups() {
    return Model.hasMany<Group>('groups').call(this);
  }

  isAdmin() {
    return Model.attribute<boolean | undefined>('isAdmin').call(this);
  }

  joinTime() {
    return Model.attribute('joinTime', Model.transformDate).call(this);
  }

  lastSeenAt() {
    return Model.attribute('lastSeenAt', Model.transformDate).call(this);
  }

  markedAllAsReadAt() {
    return Model.attribute('markedAllAsReadAt', Model.transformDate).call(this);
  }

  unreadNotificationCount() {
    return Model.attribute<number | undefined>('unreadNotificationCount').call(this);
  }
  newNotificationCount() {
    return Model.attribute<number | undefined>('newNotificationCount').call(this);
  }

  discussionCount() {
    return Model.attribute<number | undefined>('discussionCount').call(this);
  }
  commentCount() {
    return Model.attribute<number | undefined>('commentCount').call(this);
  }

  canEdit() {
    return Model.attribute<boolean | undefined>('canEdit').call(this);
  }
  canEditCredentials() {
    return Model.attribute<boolean | undefined>('canEditCredentials').call(this);
  }
  canEditGroups() {
    return Model.attribute<boolean | undefined>('canEditGroups').call(this);
  }
  canDelete() {
    return Model.attribute<boolean | undefined>('canDelete').call(this);
  }

  color() {
    return computed<string, User>('displayName', 'avatarUrl', 'avatarColor', (displayName, avatarUrl, avatarColor) => {
      // 如果我们已经计算并缓存了用户头像的主要颜色，那么我们可以以RGB格式返回它。
      // 如果我们还没有计算，我们会想要计算它。除非用户没有头像，在这种情况下，我们将从他们的显示名称生成颜色。
      if (avatarColor) {
        return `rgb(${(avatarColor as Color).join(', ')})`;
      } else if (avatarUrl) {
        this.calculateAvatarColor();
        return '';
      }

      return '#' + stringToColor(displayName as string);
    }).call(this);
  }

  protected avatarColor: Color | null = null;

  /**
   * 检查用户是否在过去5分钟内被看到。
   */
  isOnline(): boolean {
    return dayjs().subtract(5, 'minutes').isBefore(this.lastSeenAt());
  }

  /**
   * 获取适用于此用户的徽章组件。
   */
  badges(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const groups = this.groups();

    if (groups) {
      groups.forEach((group) => {
        items.add(`group${group?.id()}`, <GroupBadge group={group} />);
      });
    }

    return items;
  }

  /**
   * 计算用户头像的主要颜色。一旦计算完成，主要颜色将被设置为 `avatarColor` 属性。
   */
  protected calculateAvatarColor() {
    const image = new Image();
    const user = this;

    image.addEventListener('load', function (this: HTMLImageElement) {
      try {
        const colorThief = new ColorThief();
        user.avatarColor = colorThief.getColor(this);
      } catch (e) {
        // 由于 color thief 的一个缺陷，完全白色的头像会抛出错误
        // 参见 https://github.com/lokesh/color-thief/issues/40
        if (e instanceof TypeError) {
          user.avatarColor = [255, 255, 255];
        } else {
          throw e;
        }
      }
      user.freshness = new Date();
      m.redraw();
    });
    image.crossOrigin = 'anonymous';
    image.src = this.avatarUrl() ?? '';
  }

  /**
   * 更新用户的偏好设置。
   */
  savePreferences(newPreferences: Record<string, unknown>): Promise<this> {
    const preferences = this.preferences();

    Object.assign(preferences ?? {}, newPreferences);

    return this.save({ preferences });
  }
}
