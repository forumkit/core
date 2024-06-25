import app from '../../common/app';
import Model from '../Model';
import computed from '../utils/computed';
import ItemList from '../utils/ItemList';
import Badge from '../components/Badge';
import Mithril from 'mithril';
import Post from './Post';
import User from './User';

export default class Discussion extends Model {
  title() {
    return Model.attribute<string>('title').call(this);
  }
  slug() {
    return Model.attribute<string>('slug').call(this);
  }

  createdAt() {
    return Model.attribute<Date | undefined, string | undefined>('createdAt', Model.transformDate).call(this);
  }
  user() {
    return Model.hasOne<User | null>('user').call(this);
  }
  firstPost() {
    return Model.hasOne<Post | null>('firstPost').call(this);
  }

  lastPostedAt() {
    return Model.attribute('lastPostedAt', Model.transformDate).call(this);
  }
  lastPostedUser() {
    return Model.hasOne<User | null>('lastPostedUser').call(this);
  }
  lastPost() {
    return Model.hasOne<Post | null>('lastPost').call(this);
  }
  lastPostNumber() {
    return Model.attribute<number | null | undefined>('lastPostNumber').call(this);
  }

  commentCount() {
    return Model.attribute<number | undefined>('commentCount').call(this);
  }
  replyCount() {
    return computed<number, this>('commentCount', (commentCount) => Math.max(0, ((commentCount as number) ?? 0) - 1)).call(this);
  }
  posts() {
    return Model.hasMany<Post>('posts').call(this);
  }
  mostRelevantPost() {
    return Model.hasOne<Post | null>('mostRelevantPost').call(this);
  }

  lastReadAt() {
    return Model.attribute('lastReadAt', Model.transformDate).call(this);
  }
  lastReadPostNumber() {
    return Model.attribute<number | null | undefined>('lastReadPostNumber').call(this);
  }
  isUnread() {
    return computed<boolean, this>('unreadCount', (unreadCount) => !!unreadCount).call(this);
  }
  isRead() {
    return computed<boolean, this>('unreadCount', (unreadCount) => !!(app.session.user && !unreadCount)).call(this);
  }

  hiddenAt() {
    return Model.attribute('hiddenAt', Model.transformDate).call(this);
  }
  hiddenUser() {
    return Model.hasOne<User | null>('hiddenUser').call(this);
  }
  isHidden() {
    return computed<boolean, this>('hiddenAt', (hiddenAt) => !!hiddenAt).call(this);
  }

  canReply() {
    return Model.attribute<boolean | undefined>('canReply').call(this);
  }
  canRename() {
    return Model.attribute<boolean | undefined>('canRename').call(this);
  }
  canHide() {
    return Model.attribute<boolean | undefined>('canHide').call(this);
  }
  canDelete() {
    return Model.attribute<boolean | undefined>('canDelete').call(this);
  }

  /**
   * 从讨论的帖子关系中移除一个帖子。
   */
  removePost(id: string): void {
    const posts = this.rawRelationship<Post[]>('posts');

    if (!posts) {
      return;
    }

    posts.some((data, i) => {
      if (id === data.id) {
        posts.splice(i, 1);
        return true;
      }

      return false;
    });
  }

  /**
   * 获取当前用户在此讨论中未读帖子的估计数量。
   */
  unreadCount(): number {
    const user = app.session.user;

    if (user && (user.markedAllAsReadAt()?.getTime() ?? 0) < this.lastPostedAt()?.getTime()!) {
      const unreadCount = Math.max(0, (this.lastPostNumber() ?? 0) - (this.lastReadPostNumber() || 0));
      // 计算未读帖子数量
      // 如果帖子被删除，可能会导致未读数量超过实际帖子数量。因此，我们取两者中的最小值来确保不会出现此问题。
      return Math.min(unreadCount, this.commentCount() ?? 0);
    }

    return 0;
  }

  /**
   * 获取适用于此讨论的徽章组件。
   */
  badges(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.isHidden()) {
      items.add('hidden', <Badge type="hidden" icon="fas fa-trash" label={app.translator.trans('core.lib.badge.hidden_tooltip')} />);
    }

    return items;
  }

  /**
   * 获取此讨论中所有帖子的ID列表。
   */
  postIds(): string[] {
    return this.rawRelationship<Post[]>('posts')?.map((link) => link.id) ?? [];
  }
}
