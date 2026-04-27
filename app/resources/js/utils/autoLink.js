/**
 * 텍스트 내 URL을 클릭 가능한 <a> 태그로 변환합니다.
 * v-html 과 함께 사용합니다.
 */
export function autoLink(text) {
  if (!text) return ''
  // XSS 방지를 위해 HTML 특수문자 먼저 이스케이프
  const escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
  // http/https URL을 <a> 태그로 변환
  return escaped.replace(
    /(https?:\/\/[^\s<>"&]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:#2563EB;text-decoration:underline;word-break:break-all;">$1</a>'
  )
}
