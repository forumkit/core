import app from '../app';

/**
 * 如果站点处于调试模式，则使用提供的参数调用`console.warn`。
 *
 * 此函数旨在为扩展开发人员提供有关其扩展可能难以在测试中发现的问题的警告，例如可访问性问题。
 *
 * 这些警告在生产站点上应该被隐藏，以确保网站管理员不会被那些告诉他们存在问题但实际上他们无法解决的好心人淹没。
 */
export default function fireDebugWarning(...args: Parameters<typeof console.warn>): void {
  if (!app.site.attribute('debug')) return;

  console.warn(...args);
}

/**
 * 触发一个显示在JavaScript控制台中的Forumkit弃用警告。
 *
 * 这些警告仅在站点处于调试模式时显示，此函数的存在是为了减少我们JavaScript中多个警告导致的包大小。
 *
 * @param message 要显示的消息。（请简短而直接！）
 * @param githubId 与此更改相关的更多信息的PR或问题ID。
 * @param [removedFrom] 此功能将被完全删除的版本。（默认为2.0）
 * @param [repo] 问题或PR所在的仓库。（默认为forumkit/core）
 *
 * @see {@link fireDebugWarning}
 */
export function fireDeprecationWarning(message: string, githubId: string, removedFrom: string = '2.0', repo: string = 'forumkit/core'): void {
  // GitHub会自动在`/pull`和`/issues`之间进行重定向，所以使用`/pull`可以节省2个字节！
  fireDebugWarning(`[Forumkit ${removedFrom} Deprecation] ${message}\n\nSee: https://github.com/${repo}/pull/${githubId}`);
}
