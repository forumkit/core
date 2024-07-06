import app from '../../site/app';
import ComposerBody from './ComposerBody';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';
import extractText from '../../common/utils/extractText';

function minimizeComposerIfFullScreen(e) {
  if (app.composer.isFullScreen()) {
    app.composer.minimize();
    e.stopPropagation();
  }
}

/**
 * `ReplyComposer` 组件用于显示回复讨论的编辑器内容。
 *
 * ### 属性（Attrs）
 *
 * - ComposerBody 的所有属性
 * - `discussion` 讨论
 */
export default class ReplyComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('core.site.composer_reply.body_placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.site.composer_reply.submit_button');
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('core.site.composer_reply.discard_confirmation'));
  }

  headerItems() {
    const items = super.headerItems();
    const discussion = this.attrs.discussion;

    items.add(
      'title',
      <h3>
        {icon('fas fa-reply')}{' '}
        <Link href={app.route.discussion(discussion)} onclick={minimizeComposerIfFullScreen}>
          {discussion.title()}
        </Link>
      </h3>
    );

    return items;
  }

  /**
   * 当由文本编辑器触发时，跳转到预览页面。
   */
  jumpToPreview(e) {
    minimizeComposerIfFullScreen(e);

    m.route.set(app.route.discussion(this.attrs.discussion, 'reply'));
  }

  /**
   * 获取回复保存时提交给服务器的数据。
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      content: this.composer.fields.content(),
      relationships: { discussion: this.attrs.discussion },
    };
  }

  onsubmit() {
    const discussion = this.attrs.discussion;

    this.loading = true;
    m.redraw();

    const data = this.data();

    app.store
      .createRecord('posts')
      .save(data)
      .then((post) => {
        // 如果当前正在查看包含该回复的讨论，则我们可以更新帖子流并滚动到该帖子
        if (app.viewingDiscussion(discussion)) {
          const stream = app.current.get('stream');
          stream.update().then(() => stream.goToNumber(post.number()));
        } else {
          // 否则，我们将创建一个警告消息来通知用户他们的回复已发布
          // 该消息包含一个按钮，点击后会跳转到他们的新帖子
          let alert;
          const viewButton = (
            <Button
              className="Button Button--link"
              onclick={() => {
                m.route.set(app.route.post(post));
                app.alerts.dismiss(alert);
              }}
            >
              {app.translator.trans('core.site.composer_reply.view_button')}
            </Button>
          );
          alert = app.alerts.show(
            {
              type: 'success',
              controls: [viewButton],
            },
            app.translator.trans('core.site.composer_reply.posted_message')
          );
        }

        this.composer.hide();
      }, this.loaded.bind(this));
  }
}
