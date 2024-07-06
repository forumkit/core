/**
 * `anchorScroll` 工具函数用于保存相对于某个元素的滚动位置，并在回调函数执行后恢复该位置。
 *
 * 如果重新绘制会改变视口上方的页面内容，那么这将非常有用。通常这样做会导致视口中的内容被推下或拉上。通过使用此工具函数来包装重新绘制的过程，滚动位置可以锚定到视口中或下方的某个元素，从而使视口中的内容保持不变。
 *
 * @param {string | HTMLElement | SVGElement | Element} element 要锚定滚动位置的元素
 * @param {() => void} callback 将改变页面内容的回调函数
 */
export default function anchorScroll(element, callback) {
  const $window = $(window);
  const relativeScroll = $(element).offset().top - $window.scrollTop();

  callback();

  $window.scrollTop($(element).offset().top - relativeScroll);
}
