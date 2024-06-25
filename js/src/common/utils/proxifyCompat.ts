export default function proxifyCompat(compat: Record<string, unknown>, namespace: string) {
  // 正则表达式，用于替换核心及核心扩展的 'common/' 和 'NAMESPACE/'
  // 并移除 .js, .ts 和 .tsx 扩展名
  // 例如： admin/utils/extract --> utils/extract
  // 例如： tags/common/utils/sortTags --> tags/utils/sortTags
  const regex = new RegExp(String.raw`(\w+\/)?(${namespace}|common)\/`);
  const fileExt = /(\.js|\.tsx?)$/;

  return new Proxy(compat, {
    get: (obj, prop: string) => obj[prop] || obj[prop.replace(regex, '$1').replace(fileExt, '')],
  });
}
