import app from '../app';

/**
 * 如果论坛处于调试模式，则使用提供的参数调用 `console.warn`。
 *
 * 此函数旨在为扩展开发人员提供警告，关于他们的扩展可能存在的问题，这些问题在测试时可能不容易被注意到，比如无障碍性问题。
 *
 * 这些警告在生产环境的论坛上应该被隐藏，以确保网站管理员不会被那些告诉他们存在他们无法解决的问题的好心人淹没。
 */
export default function fireDebugWarning(...args: Parameters<typeof console.warn>): void {
  if (!app.forum.attribute('debug')) return;

  console.warn(...args);
}

/**
 * 触发一个显示在JS控制台中的Forumkit弃用警告。
 *
 * 这些警告仅在论坛处于调试模式时显示，该函数的存在是为了减少由于我们JavaScript中多个警告导致的包大小。
 *
 * @param message 要显示的消息。（请简短但清晰！）
 * @param githubId 与此更改相关的PR或Issue ID。
 * @param [removedFrom] 此功能将完全移除的版本。（默认为 '2.0'）
 * @param [repo] 问题或PR所在的仓库。（默认为 'forumkit/core'）
 *
 * @see {@link fireDebugWarning}
 */
export function fireDeprecationWarning(message: string, githubId: string, removedFrom: string = '2.0', repo: string = 'forumkit/core'): void {
  fireDebugWarning(`[Forumkit ${removedFrom} Deprecation] ${message}\n\nSee: https://github.com/${repo}/pull/${githubId}`);
}
