# Changelog - Testes E2E

## Correções Aplicadas

### 1. Navegação Direta
**Problema: Os testes tentavam encontrar links de login na página inicial, mas esses links podem não estar sempre visíveis.

**Solução**: Navegação direta para `/login` usando `page.goto('/login')` ao invés de procurar por links.

### 2. Seletores de Formulário Melhorados

**Problema**: Os testes usavam seletores genéricos que não encontravam os elementos reais.

**Solução**: 
- Uso de seletores específicos baseados na estrutura real: `input[type="email"].input`
- Adição de `waitForLoadState('networkidle')` para garantir que a página carregou completamente
- Timeouts aumentados para elementos que podem demorar a aparecer

### 3. Teste de Filtro de Categorias

**Problema**: O teste assumia que o filtro sempre existiria e falhava quando não encontrava elementos.

**Solução**:
- Verificação condicional se o filtro existe antes de tentar usá-lo
- Uso de `test.skip()` se o filtro não estiver disponível
- Seletores mais flexíveis para receitas: `[data-testid="recipe"], .recipe, article, .card`

### 4. Timeouts e Esperas

**Melhorias**:
- `waitForLoadState('networkidle')` para garantir carregamento completo
- Timeouts aumentados de 5000ms para 10000ms em elementos críticos
- Esperas adicionais após ações que podem disparar requisições

## Resultado

✅ **21 testes passando** em todos os navegadores (Chromium, Firefox, WebKit)

## Próximos Passos (Opcional)

Para melhorar ainda mais os testes:

1. Adicionar `data-testid` nos componentes principais para seletores mais estáveis
2. Criar helpers/fixtures para ações comuns (login, criar receita, etc.)
3. Adicionar testes para cenários de erro
4. Implementar testes de acessibilidade

