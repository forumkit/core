import app from '../../admin/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';

/**
 * 一个自定义的路由解析器，用于 ExtensionPage，它生成并处理指向默认扩展页面或由扩展提供的页面的路由。
 */
export default class ExtensionPageResolver<
  Attrs extends ExtensionPageAttrs = ExtensionPageAttrs,
  RouteArgs extends Record<string, unknown> = {}
> extends DefaultResolver<Attrs, ExtensionPage<Attrs>, RouteArgs> {
  static extension: string | null = null;

  onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string) {
    const extensionPage = app.extensionData.getPage<Attrs>(args.id);

    if (extensionPage) {
      return extensionPage;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
