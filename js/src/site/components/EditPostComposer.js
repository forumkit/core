import app from '../../site/app';
import ComposerBody from './ComposerBody';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';

function minimizeComposerIfFullScreen(e) {
  if (app.composer.isFullScreen()) {
    app.composer.minimize();
    e.stopPropagation();
  }
}

/**
 * `EditPostComposer` 组件用于显示编辑帖子的编辑器内容。它设置初始内容为正在编辑的帖子的内容，并添加一个标题控件来指示正在编辑哪个帖子。
 *
 * ### 属性 Attrs
 *
 * - ComposerBody 的所有属性
 * - `post` 帖子
 */
export default class EditPostComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.site.composer_edit.submit_button');
    attrs.confirmExit = attrs.confirmExit || app.translator.trans('core.site.composer_edit.discard_confirmation');
    attrs.originalContent = attrs.originalContent || attrs.post.content();
    attrs.user = attrs.user || attrs.post.user();

    attrs.post.editedContent = attrs.originalContent;
  }

  headerItems() {
    const items = super.headerItems();
    const post = this.attrs.post;

    items.add(
      'title',
      <h3>
        {icon('fas fa-pencil-alt')}{' '}
        <Link href={app.route.discussion(post.discussion(), post.number())} onclick={minimizeComposerIfFullScreen}>
          {app.translator.trans('core.site.composer_edit.post_link', { number: post.number(), discussion: post.discussion().title() })}
        </Link>
      </h3>
    );

    return items;
  }

  /**
   * 当由文本编辑器触发时，跳转到预览。
   */
  jumpToPreview(e) {
    minimizeComposerIfFullScreen(e);

    m.route.set(app.route.post(this.attrs.post));
  }

  /**
   * 获取当帖子保存时需要提交到服务器的数据。
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    const discussion = this.attrs.post.discussion();

    this.loading = true;

    const data = this.data();

    this.attrs.post.save(data).then((post) => {
      // 如果我们当前正在查看此编辑所在的讨论，则我们可以滚动到帖子。
      if (app.viewingDiscussion(discussion)) {
        app.current.get('stream').goToNumber(post.number());
      } else {
        // 否则，我们将创建一个警告消息来通知用户他们的编辑已经完成，
        // 消息中包含一个按钮，点击后会跳转到他们编辑的帖子。
        const alert = app.alerts.show(
          {
            type: 'success',
            controls: [
              <Button
                className="Button Button--link"
                onclick={() => {
                  m.route.set(app.route.post(post));
                  app.alerts.dismiss(alert);
                }}
              >
                {app.translator.trans('core.site.composer_edit.view_button')}
              </Button>,
            ],
          },
          app.translator.trans('core.site.composer_edit.edited_message')
        );
      }

      this.composer.hide();
    }, this.loaded.bind(this));
  }
}
