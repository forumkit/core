import app from '../common/app';
import User from './models/User';
import { ForumkitRequestOptions } from './Application';

export type LoginParams = {
  /**
   * The username/email
   */
  identification: string;
  password: string;
  remember: boolean;
};

/**
 * `Session` 类定义了当前用户的会话。它存储对当前已验证用户的引用，并提供登录/注销的方法。
 */
export default class Session {
  /**
   * 当前已验证的用户。
   */
  user: User | null; // User 类型或 null

  /**
   * CSRF 令牌
   */
  csrfToken: string; // CSRF 令牌字符串

  constructor(user: User | null, csrfToken: string) {
    this.user = user;
    this.csrfToken = csrfToken;
  }

  /**
   * 尝试登录用户。
   */
  login(body: LoginParams, options: Omit<ForumkitRequestOptions<any>, 'url' | 'body' | 'method'> = {}) {
    return app.request({
      method: 'POST',
      url: `${app.site.attribute('baseUrl')}/login`,
      body,
      ...options,
    });
  }

  /**
   * 注销用户
   */
  logout() {
    window.location.href = `${app.site.attribute('baseUrl')}/logout?token=${this.csrfToken}`;
  }
}
