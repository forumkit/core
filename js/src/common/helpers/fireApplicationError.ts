import app from '../app';

/**
 * 触发一个Forumkit错误，该错误将在JS控制台中显示给所有人，并在管理员的警告框中显示。
 *
 * @param userTitle: 错误的用户友好标题，应本地化。
 * @param consoleTitle: 将在控制台中显示的错误标题，无需本地化。
 * @param error: 错误信息。
 */
export default function fireApplicationError(userTitle: string, consoleTitle: string, error: any) {
  console.group(`%c${consoleTitle}`, 'background-color: #d83e3e; color: #ffffff; font-weight: bold;');
  console.error(error);
  console.groupEnd();

  if (app.session?.user?.isAdmin()) {
    app.alerts.show({ type: 'error' }, `${userTitle}`);
  }
}
