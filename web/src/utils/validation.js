import i18n from '@/i18n'

/**
 * Traduz uma mensagem de erro do Laravel para o idioma atual
 */
export function translateValidationError(errorMessage, field) {
  if (!errorMessage) return ''
  
  const t = i18n.global.t

  // Mapeamento de campos em inglês para chaves de tradução
  const fieldMap = {
    'name': 'validation.name',
    'category_id': 'validation.category',
    'prep_time_minutes': 'validation.prepTime',
    'servings': 'validation.servings',
    'image': 'validation.image',
    'instructions': 'validation.instructions',
    'ingredients': 'validation.ingredients',
  }

  const fieldKey = fieldMap[field]
  let fieldName = ''
  
  if (fieldKey) {
    try {
      fieldName = t(fieldKey)
    } catch (e) {
      fieldName = field
    }
  } else {
    fieldName = field
  }

  const errorLower = errorMessage.toLowerCase()

  // Padrões de erro comuns do Laravel
  if (errorLower.includes('required') || errorLower.includes('é obrigatório') || errorLower.includes('es obligatorio')) {
    return t('validation.required', { field: fieldName })
  }

  if (errorLower.includes('must not be greater than') || errorLower.includes('não pode ter mais de') || errorLower.includes('no puede tener más de')) {
    const match = errorMessage.match(/(\d+)\s+characters?/i) || errorMessage.match(/(\d+)\s+caracteres?/i)
    if (match) {
      return t('validation.max', { field: fieldName, max: match[1] })
    }
  }

  if (errorLower.includes('must be at least') || errorLower.includes('deve ter pelo menos') || errorLower.includes('debe tener al menos')) {
    const match = errorMessage.match(/(\d+)\s+characters?/i) || errorMessage.match(/(\d+)\s+caracteres?/i)
    if (match) {
      return t('validation.min', { field: fieldName, min: match[1] })
    }
  }

  if (errorLower.includes('must be a number') || errorLower.includes('deve ser um número') || errorLower.includes('debe ser un número')) {
    return t('validation.numeric', { field: fieldName })
  }

  if (errorLower.includes('must be a valid email') || errorLower.includes('deve ser um e-mail válido') || errorLower.includes('debe ser un correo electrónico válido')) {
    return t('validation.email', { field: fieldName })
  }

  if (errorLower.includes('must be an image') || errorLower.includes('deve ser uma imagem') || errorLower.includes('debe ser una imagen')) {
    return t('validation.image', { field: fieldName })
  }

  if (errorLower.includes('must be a file of type') || errorLower.includes('deve ser um arquivo do tipo') || errorLower.includes('debe ser un archivo de tipo')) {
    const match = errorMessage.match(/type:\s*(.+)/i) || errorMessage.match(/tipo:\s*(.+)/i)
    if (match) {
      return t('validation.mimes', { field: fieldName, types: match[1] })
    }
  }

  if (errorLower.includes('kilobytes') || errorLower.includes('kilobytes')) {
    const match = errorMessage.match(/(\d+)\s+kilobytes?/i) || errorMessage.match(/(\d+)\s+kilobytes?/i)
    if (match) {
      return t('validation.maxSize', { field: fieldName, size: match[1] + ' KB' })
    }
  }

  // Se não encontrar padrão conhecido, retorna a mensagem original
  return errorMessage
}

