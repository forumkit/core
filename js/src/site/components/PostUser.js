import app from '../../site/app';
import Component from '../../common/Component';
import Link from '../../common/components/Link';
import UserCard from './UserCard';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';

/**
 * `PostUser` 组件显示帖子的作者的头像和用户名。
 *
 * ### 属性（Attrs）
 *
 * - `post` 帖子
 */
export default class PostUser extends Component {
  view() {
    const post = this.attrs.post;
    const user = post.user();

    if (!user) {
      return (
        <div className="PostUser">
          <h3 className="PostUser-name">
            {avatar(user, { className: 'PostUser-avatar' })} {username(user)}
          </h3>
        </div>
      );
    }

    return (
      <div className="PostUser">
        <h3 className="PostUser-name">
          <Link href={app.route.user(user)}>
            {avatar(user, { className: 'PostUser-avatar' })}
            {userOnline(user)}
            {username(user)}
          </Link>
        </h3>
        <ul className="PostUser-badges badges">{listItems(user.badges().toArray())}</ul>

        {!post.isHidden() && this.attrs.cardVisible && (
          <UserCard user={user} className="UserCard--popover" controlsButtonClassName="Button Button--icon Button--flat" />
        )}
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    let timeout;

    this.$()
      .on('mouseover', '.PostUser-name a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.showCard.bind(this), 500);
      })
      .on('mouseout', '.PostUser-name a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.hideCard.bind(this), 250);
      });
  }

  /**
   * 显示用户卡片。
   */
  showCard() {
    this.attrs.oncardshow();

    setTimeout(() => this.$('.UserCard').addClass('in'));
  }

  /**
   * 隐藏用户卡片。
   */
  hideCard() {
    this.$('.UserCard')
      .removeClass('in')
      .one('transitionend webkitTransitionEnd oTransitionEnd', () => {
        this.attrs.oncardhide();
      });
  }
}
