/*
 * 原始版权归 GitHub, Inc. 所有，受 MIT 许可证的约束。
 * 请在 https://github.com/github/markdown-toolbar-element/blob/master/LICENSE 查看许可证文本。
 */

export interface SelectionRange {
  text: string;
  selectionStart: number | undefined;
  selectionEnd: number | undefined;
}

let canInsertText: boolean | null = null;

export default function insertText(textarea: HTMLTextAreaElement, { text, selectionStart, selectionEnd }: SelectionRange) {
  const originalSelectionStart = textarea.selectionStart;
  const before = textarea.value.slice(0, originalSelectionStart);
  const after = textarea.value.slice(textarea.selectionEnd);

  if (canInsertText === null || canInsertText === true) {
    textarea.contentEditable = 'true';
    try {
      canInsertText = document.execCommand('insertText', false, text);
    } catch (error) {
      canInsertText = false;
    }
    textarea.contentEditable = 'false';
  }

  if (canInsertText && !textarea.value.slice(0, textarea.selectionStart).endsWith(text)) {
    canInsertText = false;
  }

  if (!canInsertText) {
    textarea.value = before + text + after;
    textarea.dispatchEvent(new CustomEvent('input', { bubbles: true, cancelable: true }));
  }

  if (selectionStart != null && selectionEnd != null) {
    textarea.setSelectionRange(selectionStart, selectionEnd);
  } else {
    textarea.setSelectionRange(originalSelectionStart, textarea.selectionEnd);
  }
}
