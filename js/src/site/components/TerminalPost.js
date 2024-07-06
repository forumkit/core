import app from '../../site/app';
import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import icon from '../../common/helpers/icon';

/**
 * 显示讨论中第一条或最后一条帖子的信息。
 *
 * ### 属性
 *
 * - `discussion`
 * - `lastPost`
 */
export default class TerminalPost extends Component {
  view() {
    const discussion = this.attrs.discussion;
    const lastPost = this.attrs.lastPost && discussion.replyCount();

    const user = discussion[lastPost ? 'lastPostedUser' : 'user']();
    const time = discussion[lastPost ? 'lastPostedAt' : 'createdAt']();

    return (
      <span>
        {!!lastPost && icon('fas fa-reply')}{' '}
        {app.translator.trans('core.site.discussion_list.' + (lastPost ? 'replied' : 'posted_on') + '_text', {
          user,
          ago: humanTime(time),
        })}
      </span>
    );
  }
}
