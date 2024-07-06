import app from '../../site/app';
import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import DiscussionControls from '../utils/DiscussionControls';
import ComposerPostPreview from './ComposerPostPreview';
import listItems from '../../common/helpers/listItems';

/**
 * `ReplyPlaceholder` 组件用于显示回复的占位符，点击该占位符时会打开回复编辑器。
 *
 * ### 属性（Attrs）
 *
 * - `discussion` 讨论
 */
export default class ReplyPlaceholder extends Component {
  view() {
    if (app.composer.composingReplyTo(this.attrs.discussion)) {
      return (
        <article className="Post CommentPost editing" aria-busy="true">
          <header className="Post-header">
            <div className="PostUser">
              <h3 className="PostUser-name">
                {avatar(app.session.user, { className: 'PostUser-avatar' })}
                {username(app.session.user)}
              </h3>
              <ul className="PostUser-badges badges">{listItems(app.session.user.badges().toArray())}</ul>
            </div>
          </header>
          <ComposerPostPreview className="Post-body" composer={app.composer} surround={this.anchorPreview.bind(this)} />
        </article>
      );
    }

    const reply = () => {
      DiscussionControls.replyAction.call(this.attrs.discussion, true).catch(() => {});
    };

    return (
      <button className="Post ReplyPlaceholder" onclick={reply}>
        <span className="Post-header">
          {avatar(app.session.user, { className: 'PostUser-avatar' })} {app.translator.trans('core.site.post_stream.reply_placeholder')}
        </span>
      </button>
    );
  }

  anchorPreview(preview) {
    const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

    preview();

    if (anchorToBottom) {
      $(window).scrollTop($(document).height());
    }
  }
}
