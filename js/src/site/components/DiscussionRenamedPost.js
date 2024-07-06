import app from '../../site/app';
import EventPost from './EventPost';
import extractText from '../../common/utils/extractText';
import Tooltip from '../../common/components/Tooltip';

/**
 * `DiscussionRenamedPost` 组件显示一个讨论事件帖子，表明该讨论已被重命名。
 *
 * ### 属性（Attrs）
 *
 * - 所有 EventPost 的属性都适用
 */
export default class DiscussionRenamedPost extends EventPost {
  icon() {
    return 'fas fa-pencil-alt';
  }

  description(data) {
    const renamed = app.translator.trans('core.site.post_stream.discussion_renamed_text', data);

    return <span>{renamed}</span>;
  }

  descriptionData() {
    const post = this.attrs.post;
    const oldTitle = post.content()[0];
    const newTitle = post.content()[1];

    return {
      new: (
        <Tooltip text={extractText(app.translator.trans('core.site.post_stream.discussion_renamed_old_tooltip', { old: oldTitle }))}>
          <strong className="DiscussionRenamedPost-new">{newTitle}</strong>
        </Tooltip>
      ),
    };
  }
}
