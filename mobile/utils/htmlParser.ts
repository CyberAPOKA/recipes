/**
 * Converte HTML simples (ul/ol com li) em texto formatado
 */
export function parseHtmlToText(html: string): string {
  if (!html) return '';

  // Remove tags HTML e converte em texto formatado
  let text = html
    // Remove tags de lista e converte li em linhas numeradas ou com bullet
    .replace(/<ol[^>]*>/gi, '')
    .replace(/<\/ol>/gi, '')
    .replace(/<ul[^>]*>/gi, '')
    .replace(/<\/ul>/gi, '')
    .replace(/<li[^>]*>/gi, '• ')
    .replace(/<\/li>/gi, '\n')
    // Remove outras tags HTML
    .replace(/<[^>]+>/g, '')
    // Decodifica entidades HTML
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&apos;/g, "'")
    // Remove espaços extras
    .replace(/\n\s*\n/g, '\n')
    .trim();

  return text;
}

