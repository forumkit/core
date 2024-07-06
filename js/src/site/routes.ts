import SiteApplication from './SiteApplication';
import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import SettingsPage from './components/SettingsPage';
import NotificationsPage from './components/NotificationsPage';
import DiscussionPageResolver from './resolvers/DiscussionPageResolver';
import Discussion from '../common/models/Discussion';
import type Post from '../common/models/Post';
import type User from '../common/models/User';
import UserSecurityPage from './components/UserSecurityPage';

/**
 * 生成表单页面URL的辅助函数。
 */
export interface SiteRoutes {
  discussion: (discussion: Discussion, near?: number) => string;
  post: (post: Post) => string;
  user: (user: User) => string;
}

/**
 * `routes` 初始化器定义了站点应用的路由。
 */
export default function (app: SiteApplication) {
  app.routes = {
    index: { path: '/all', component: IndexPage },

    discussion: { path: '/discussion/:id', component: DiscussionPage, resolverClass: DiscussionPageResolver },
    'discussion.near': { path: '/discussion/:id/:near', component: DiscussionPage, resolverClass: DiscussionPageResolver },

    user: { path: '/@:username', component: PostsUserPage },
    'user.posts': { path: '/@:username', component: PostsUserPage },
    'user.discussions': { path: '/@:username/discussions', component: DiscussionsUserPage },

    settings: { path: '/settings', component: SettingsPage },
    'user.security': { path: '/@:username/security', component: UserSecurityPage },
    notifications: { path: '/notifications', component: NotificationsPage },
  };
}

export function makeRouteHelpers(app: SiteApplication) {
  return {
    /**
     * 生成指向讨论的URL。
     */
    discussion: (discussion: Discussion, near?: number) => {
      return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
        id: discussion.slug(),
        near: near && near !== 1 ? near : undefined,
      });
    },

    /**
     * 生成指向帖子的URL。
     */
    post: (post: Post) => {
      return app.route.discussion(post.discussion(), post.number());
    },

    /**
     * 生成指向用户的URL。
     */
    user: (user: User) => {
      return app.route('user', {
        username: user.slug(),
      });
    },
  };
}
