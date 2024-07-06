/**
 * `slidable` 实用程序为元素添加触摸手势，以便可以滑动以显示下方的控件，然后释放以激活这些控件。
 *
 * 它依赖于具有特定 CSS 类的子元素。
 *
 * 函数返回一个包含 `reset` 属性的记录。这是一个函数，它将滑块重置到其原始位置。当控件下拉菜单关闭时，应该调用此函数。
 *
 * @param {HTMLElement | SVGElement | Element} element
 * @return {{ reset : () => void }}
 */
export default function slidable(element) {
  const $element = $(element);
  const threshold = 50;

  let $underneathLeft;
  let $underneathRight;

  let startX;
  let startY;
  let couldBeSliding = false;
  let isSliding = false;
  let pos = 0;

  /**
   * 将滑块动画到新的位置。
   *
   * @param {number} newPos
   * @param {Partial<JQueryAnimationOptions>} [options]
   */
  const animatePos = (newPos, options = {}) => {
    // 由于我们不能使用 jQuery 来动画变换属性，我们将使用一个变通方法。我们设置一个具有 step 函数的动画，该函数将设置变换属性，但然后我们使用 jQuery 动画一个未使用的属性 (background-position-x) 。
    options.duration ||= 'fast';
    options.step = function (x) {
      $(this).css('transform', 'translate(' + x + 'px, 0)');
    };

    $element.find('.Slidable-content').animate({ 'background-position-x': newPos }, options);
  };

  /**
   * 将滑块重置到其原始位置。
   */
  const reset = () => {
    animatePos(0, {
      complete: function () {
        $element.removeClass('sliding');
        $underneathLeft.hide();
        $underneathRight.hide();
        isSliding = false;
      },
    });
  };

  $element
    .find('.Slidable-content')
    .on('touchstart', function (e) {
      // 更新在滑块下的元素的引用，只要它们没有被禁用。
      $underneathLeft = $element.find('.Slidable-underneath--left:not(.disabled)');
      $underneathRight = $element.find('.Slidable-underneath--right:not(.disabled)');

      startX = e.originalEvent.targetTouches[0].clientX;
      startY = e.originalEvent.targetTouches[0].clientY;

      couldBeSliding = true;
      pos = 0;
    })

    .on('touchmove', function (e) {
      const newX = e.originalEvent.targetTouches[0].clientX;
      const newY = e.originalEvent.targetTouches[0].clientY;

      // 如果用户在开始滑动后，移动的方向主要是水平方向，则认为是滑动操作。
      if (couldBeSliding && Math.abs(newX - startX) > Math.abs(newY - startY)) {
        isSliding = true;
      }
      couldBeSliding = false;

      if (isSliding) {
        pos = newX - startX;

        // 如果有控制元素在滑块下，则根据滑块的位置来显示/隐藏它们
        // 同时，当滑块滑动越远，控制元素的图标会变得越大
        const toggle = ($underneath, side) => {
          if ($underneath.length) {
            const active = side === 'left' ? pos > 0 : pos < 0;

            if (active && $underneath.hasClass('Slidable-underneath--elastic')) {
              pos -= pos * 0.5;
            }
            $underneath.toggle(active);

            const scale = Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold));
            $underneath.find('.icon').css('transform', 'scale(' + scale + ')');
          } else {
            pos = Math[side === 'left' ? 'min' : 'max'](0, pos);
          }
        };

        toggle($underneathLeft, 'left');
        toggle($underneathRight, 'right');

        $(this).css('transform', 'translate(' + pos + 'px, 0)');
        $(this).css('background-position-x', pos + 'px');

        $element.toggleClass('sliding', !!pos);

        e.preventDefault();
      }
    })

    .on('touchend', function () {
      // 如果用户在释放触摸时，滑块已经超过了某一阈值位置，则激活那一侧的控制元素
      // 并根据该侧是否是“弹性的”来动画滑动到另一侧或回到原始位置
      const activate = ($underneath) => {
        $underneath.click();

        if ($underneath.hasClass('Slidable-underneath--elastic')) {
          reset();
        } else {
          animatePos((pos > 0 ? 1 : -1) * $element.width());
        }
      };

      if ($underneathRight.length && pos < -threshold) {
        activate($underneathRight);
      } else if ($underneathLeft.length && pos > threshold) {
        activate($underneathLeft);
      } else {
        reset();
      }

      couldBeSliding = false;
      isSliding = false;
    });

  return { reset };
}
