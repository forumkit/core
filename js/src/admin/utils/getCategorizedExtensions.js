import app from '../../admin/app';

export default function getCategorizedExtensions() {
  let extensions = {};

  Object.keys(app.data.extensions).map((id) => {
    const extension = app.data.extensions[id];
    let category = extension.extra['forumkit-extension'].category;

    // 将语言包包装到新的系统中
    if (extension.extra['forumkit-locale']) {
      category = 'language';
    }

    if (category in app.extensionCategories) {
      extensions[category] = extensions[category] || [];

      extensions[category].push(extension);
    } else {
      extensions.feature = extensions.feature || [];

      extensions.feature.push(extension);
    }
  });

  return extensions;
}
