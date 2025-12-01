# Testes E2E - Guia de Configuração

## Instalação Inicial

Antes de executar os testes E2E pela primeira vez, você precisa instalar os navegadores do Playwright:

```bash
npm run test:e2e:install
```

Isso baixará os seguintes navegadores:
- Chromium (Chrome/Edge)
- Firefox
- WebKit (Safari)

## Executando os Testes

```bash
# Todos os navegadores
npm run test:e2e

# Apenas Chromium (mais rápido para desenvolvimento)
npm run test:e2e -- --project=chromium

# Interface visual interativa
npm run test:e2e:ui
```

## Notas Importantes

1. **Servidor de Desenvolvimento**: Os testes E2E iniciam automaticamente o servidor de desenvolvimento (`npm run dev`) antes de executar. Certifique-se de que a porta 5173 está disponível.

2. **Seletores**: Os testes usam seletores genéricos que podem precisar ser ajustados conforme a estrutura real da aplicação. Se um teste falhar porque não encontra um elemento, verifique:
   - Se o elemento existe na página
   - Se o seletor está correto
   - Se há algum atraso necessário (loading, animações, etc.)

3. **Timeout**: O timeout padrão é de 5 segundos. Se necessário, você pode aumentar usando `page.setDefaultTimeout()`.

## Troubleshooting

### Erro: "Executable doesn't exist"
Execute: `npm run test:e2e:install`

### Erro: "Port 5173 already in use"
Pare o servidor de desenvolvimento manual ou mude a porta no `playwright.config.ts

### Testes falhando por elementos não encontrados
- Verifique se o servidor está rodando corretamente
- Aumente o timeout se necessário
- Verifique os seletores no código da aplicação

