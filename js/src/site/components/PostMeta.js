import app from '../../site/app';
import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';

/**
 * `PostMeta` 组件用于显示帖子的时间，并在点击时显示一个下拉菜单，其中包含帖子的更多信息（编号、完整时间、永久链接）。
 *
 * ### 属性 Attrs
 *
 * - `post`
 */
export default class PostMeta extends Component {
  view() {
    const post = this.attrs.post;
    const time = post.createdAt();
    const permalink = this.getPermalink(post);
    const touch = 'ontouchstart' in document.documentElement;

    // 当下拉菜单显示时，选择永久链接输入框中的内容，以便用户可以快速复制该URL。
    const selectPermalink = function (e) {
      setTimeout(() => $(this).parent().find('.PostMeta-permalink').select());

      e.redraw = false;
    };

    return (
      <div className="Dropdown PostMeta">
        <a className="Dropdown-toggle" onclick={selectPermalink} data-toggle="dropdown">
          {humanTime(time)}
        </a>

        <div className="Dropdown-menu dropdown-menu">
          <span className="PostMeta-number">{app.translator.trans('core.site.post.number_tooltip', { number: post.number() })}</span>{' '}
          <span className="PostMeta-time">{fullTime(time)}</span> <span className="PostMeta-ip">{post.data.attributes.ipAddress}</span>
          {touch ? (
            <a className="Button PostMeta-permalink" href={permalink}>
              {permalink}
            </a>
          ) : (
            <input className="FormControl PostMeta-permalink" value={permalink} onclick={(e) => e.stopPropagation()} />
          )}
        </div>
      </div>
    );
  }

  /**
   * 获取给定帖子的永久链接。
   *
   * @param {import('../../common/models/Post').default} post
   * @returns {string}
   */
  getPermalink(post) {
    return app.site.attribute('baseOrigin') + app.route.post(post);
  }
}
