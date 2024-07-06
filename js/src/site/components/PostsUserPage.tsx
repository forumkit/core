import app from '../../site/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import Placeholder from '../../common/components/Placeholder';
import CommentPost from './CommentPost';
import type Post from '../../common/models/Post';
import type Mithril from 'mithril';
import type User from '../../common/models/User';

/**
 * `PostsUserPage` 组件在用户的个人资料中展示其活动动态。
 */
export default class PostsUserPage extends UserPage {
  /**
   * 当前活动动态是否正在加载中。
   */
  loading: boolean = true;

  /**
   * 是否还有更多的活动动态可以被加载。
   */
  moreResults: boolean = false;

  /**
   * 动态流中的帖子模型数组。
   */
  posts: Post[] = [];

  /**
   * 每次请求加载的活动动态数量。
   */
  loadLimit: number = 20;

  oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>) {
    super.oninit(vnode);

    this.loadUser(m.route.param('username'));
  }

  content() {
    if (this.posts.length === 0 && !this.loading) {
      return (
        <div className="PostsUserPage">
          <Placeholder text={app.translator.trans('core.site.user.posts_empty_text')} />
        </div>
      );
    }

    let footer;

    if (this.loading) {
      footer = <LoadingIndicator />;
    } else if (this.moreResults) {
      footer = (
        <div className="PostsUserPage-loadMore">
          <Button className="Button" onclick={this.loadMore.bind(this)}>
            {app.translator.trans('core.site.user.posts_load_more_button')}
          </Button>
        </div>
      );
    }

    return (
      <div className="PostsUserPage">
        <ul className="PostsUserPage-list">
          {this.posts.map((post) => (
            <li>
              <div className="PostsUserPage-discussion">
                {app.translator.trans('core.site.user.in_discussion_text', {
                  discussion: <Link href={app.route.post(post)}>{post.discussion().title()}</Link>,
                })}
              </div>

              <CommentPost post={post} />
            </li>
          ))}
        </ul>
        <div className="PostsUserPage-loadMore">{footer}</div>
      </div>
    );
  }

  /**
   * 初始化组件并传入用户信息，触发其活动动态的加载。
   */
  show(user: User): void {
    super.show(user);

    this.refresh();
  }

  /**
   * 清空并重新加载用户的活动动态。
   */
  refresh() {
    this.loading = true;
    this.posts = [];

    m.redraw();

    this.loadResults().then(this.parseResults.bind(this));
  }

  /**
   * 加载用户活动动态的新一页。
   *
   * @protected
   */
  loadResults(offset = 0) {
    return app.store.find<Post[]>('posts', {
      filter: {
        author: this.user!.username(),
        type: 'comment',
      },
      page: { offset, limit: this.loadLimit },
      sort: '-createdAt',
    });
  }

  /**
   * 加载更多结果。
   */
  loadMore() {
    this.loading = true;
    this.loadResults(this.posts.length).then(this.parseResults.bind(this));
  }

  /**
   * 解析结果并将其添加到活动动态流中。
   */
  parseResults(results: Post[]): Post[] {
    this.loading = false;

    this.posts.push(...results);

    this.moreResults = results.length >= this.loadLimit;
    m.redraw();

    return results;
  }
}
