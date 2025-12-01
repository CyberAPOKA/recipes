# Guia de Testes - Web (Vue.js)

Este projeto utiliza **Vitest** para testes unitários e **Playwright** para testes E2E.

## Estrutura de Testes

```
tests/
├── unit/              # Testes unitários
│   ├── components/    # Testes de componentes Vue
│   └── stores/        # Testes de stores Pinia
├── e2e/               # Testes end-to-end
│   ├── auth.spec.ts   # Testes de autenticação
│   └── recipes.spec.ts # Testes de receitas
└── setup.ts           # Configuração global dos testes
```

## Executando Testes

### Instalar dependências primeiro
```bash
npm install
```

### Testes Unitários

```bash
# Executar todos os testes unitários
npm run test

# Modo watch (re-executa ao salvar arquivos)
npm run test -- --watch

# Interface visual
npm run test:ui

# Com cobertura de código
npm run test:coverage
```

### Testes E2E

```bash
# Executar todos os testes E2E
npm run test:e2e

# Interface visual do Playwright
npm run test:e2e:ui

# Executar em modo debug
npx playwright test --debug
```

## Testes Implementados

### Testes Unitários

- **Button.test.ts**: Testa componente Button (renderização, eventos, props)
- **auth.test.ts**: Testa store de autenticação (login, logout, estado)

### Testes E2E

- **auth.spec.ts**: Testa fluxo de autenticação (login, registro)
- **recipes.spec.ts**: Testa funcionalidades de receitas (criar, filtrar, buscar)

## Escrevendo Novos Testes

### Teste Unitário de Componente

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MyComponent from '@/components/MyComponent.vue'

describe('MyComponent', () => {
  it('renders correctly', () => {
    const wrapper = mount(MyComponent, {
      props: { title: 'Test' }
    })
    
    expect(wrapper.text()).toContain('Test')
  })
})
```

### Teste de Store

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMyStore } from '@/stores/myStore'

describe('MyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initializes correctly', () => {
    const store = useMyStore()
    expect(store.value).toBe(null)
  })
})
```

### Teste E2E

```typescript
import { test, expect } from '@playwright/test'

test('user can do something', async ({ page }) => {
  await page.goto('/')
  await page.click('button')
  await expect(page.locator('.result')).toBeVisible()
})
```

## Configuração

### Vitest
- Ambiente: `happy-dom` (simula DOM)
- Setup global em `tests/setup.ts`
- Aliases configurados para `@/` apontar para `src/`

### Playwright
- Base URL: `http://localhost:5173`
- Servidor de desenvolvimento iniciado automaticamente
- Navegadores: Chromium, Firefox, WebKit

## Boas Práticas

1. **Testes Unitários**:
   - Teste comportamento, não implementação
   - Use mocks para dependências externas
   - Mantenha testes isolados

2. **Testes E2E**:
   - Teste fluxos completos do usuário
   - Use seletores estáveis (data-testid, roles)
   - Aguarde elementos aparecerem antes de interagir

3. **Nomenclatura**:
   - Use nomes descritivos
   - Agrupe testes relacionados com `describe`
   - Use `it` ou `test` para casos individuais

## Debugging

### Vitest
```bash
# Modo debug
npm run test -- --inspect-brk

# Executar teste específico
npm run test Button.test.ts
```

### Playwright
```bash
# Modo debug interativo
npx playwright test --debug

# Executar teste específico
npx playwright test auth.spec.ts

# Ver trace
npx playwright show-trace trace.zip
```

## CI/CD

Os testes podem ser executados em CI/CD:

```yaml
# Exemplo GitHub Actions
- name: Run tests
  run: |
    npm run test
    npm run test:e2e
```

