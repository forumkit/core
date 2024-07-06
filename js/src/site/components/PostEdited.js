import app from '../../site/app';
import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import Tooltip from '../../common/components/Tooltip';

/**
 * `PostEdited` 组件用于显示帖子被编辑的时间和编辑者的信息。
 *
 * ### 属性 Attrs
 *
 * - `post` 帖子对象
 */
export default class PostEdited extends Component {
  oninit(vnode) {
    super.oninit(vnode);
  }

  view() {
    const post = this.attrs.post;
    const editedUser = post.editedUser();
    const editedInfo = app.translator.trans('core.site.post.edited_tooltip', { user: editedUser, ago: humanTime(post.editedAt()) });

    return (
      <Tooltip text={editedInfo}>
        <span className="PostEdited">{app.translator.trans('core.site.post.edited_text')}</span>
      </Tooltip>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);
  }
}
