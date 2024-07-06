import app from '../../common/app';

/**
 * `punctuateSeries`  帮助程序格式化字符串列表（例如名称），以便在应用程序的区域设置中流畅地阅读。
 *
 * ```js
 * punctuateSeries(['Du', 'Flower', 'Want']) // Du, Flower, 和 Want
 * ```
 *
 * @param {import('mithril').Children[]} items
 * @return {import('mithril').Children}')}
 */
export default function punctuateSeries(items) {
  if (items.length === 2) {
    return app.translator.trans('core.lib.series.two_text', {
      first: items[0],
      second: items[1],
    });
  } else if (items.length >= 3) {
    // 如果列表中有三个或更多项目，我们将除第一个和最后一个项目外的所有项目用逗号连接起来，
    // 然后将这些项目以及第一个和最后一个项目一起传递给翻译器
    const second = items
      .slice(1, items.length - 1)
      .reduce((list, item) => list.concat([item, app.translator.trans('core.lib.series.glue_text')]), [])
      .slice(0, -1);

    return app.translator.trans('core.lib.series.three_text', {
      first: items[0],
      second,
      third: items[items.length - 1],
    });
  }

  return items;
}
