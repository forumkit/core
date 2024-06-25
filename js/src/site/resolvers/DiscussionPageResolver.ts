import type Mithril from 'mithril';

import app from '../../site/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import DiscussionPage, { IDiscussionPageAttrs } from '../components/DiscussionPage';

/**
 * DiscussionPage 的自定义路由解析器，它为同一讨论中的所有帖子生成相同的键。在相同讨论中从一个帖子跳转到另一个帖子时会触发滚动。
 */
export default class DiscussionPageResolver<
  Attrs extends IDiscussionPageAttrs = IDiscussionPageAttrs,
  RouteArgs extends Record<string, unknown> = {}
> extends DefaultResolver<Attrs, DiscussionPage<Attrs>, RouteArgs> {
  static scrollToPostNumber: number | null = null;

  /**
   * 移除讨论 slug 的可选部分，保留与讨论对象一一对应的子字符串。默认情况下，这只会从 slug 中提取数字 ID。如果使用自定义讨论 slugging 驱动程序，可能需要重写此方法。
   * @param slug
   */
  canonicalizeDiscussionSlug(slug: string | undefined) {
    if (!slug) return;
    return slug.split('-')[0];
  }

  /**
   * @inheritdoc
   */
  makeKey() {
    const params = { ...m.route.param() };
    if ('near' in params) {
      delete params.near;
    }
    params.id = this.canonicalizeDiscussionSlug(params.id);
    return this.routeName.replace('.near', '') + JSON.stringify(params);
  }

  onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string) {
    if (app.current.matches(DiscussionPage) && this.canonicalizeDiscussionSlug(args.id) === this.canonicalizeDiscussionSlug(m.route.param('id'))) {
      // 默认情况下，任何讨论的第一个帖子号都是 1
      DiscussionPageResolver.scrollToPostNumber = args.near || 1;
    }

    return super.onmatch(args, requestedPath, route);
  }

  render(vnode: Mithril.Vnode<Attrs, DiscussionPage<Attrs>>) {
    if (DiscussionPageResolver.scrollToPostNumber !== null) {
      const number = DiscussionPageResolver.scrollToPostNumber;
      // 在渲染后延迟一段时间进行滚动，以避免与渲染发生冲突
      setTimeout(() => app.current.get('stream').goToNumber(number));
      DiscussionPageResolver.scrollToPostNumber = null;
    }

    return super.render(vnode);
  }
}
