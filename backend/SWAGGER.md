# Documentação Swagger/OpenAPI

Este projeto utiliza o pacote `darkaonline/l5-swagger` para gerar documentação automática da API usando Swagger/OpenAPI.

## Acesso à Documentação

Após iniciar o servidor Laravel, a documentação Swagger estará disponível em:

```
http://localhost:8000/api/documentation
```

ou

```
http://seu-dominio.com/api/documentation
```

## Gerar/Atualizar a Documentação

Sempre que você adicionar ou modificar anotações Swagger nos controllers, é necessário regenerar a documentação:

```bash
php artisan l5-swagger:generate
```

## Estrutura das Anotações

As anotações Swagger foram adicionadas em todos os controllers:

- **AuthController**: Rotas de autenticação (register, login, logout, user)
- **CategoryController**: Listagem de categorias
- **PublicRecipeController**: Rotas públicas de receitas
- **RecipeController**: CRUD completo de receitas e scraping
- **RecipeCommentController**: Comentários em receitas
- **RecipeRatingController**: Avaliações de receitas

## Autenticação

A API utiliza Laravel Sanctum para autenticação. Para usar os endpoints protegidos:

1. Faça login através do endpoint `/api/login`
2. Copie o token retornado
3. No Swagger UI, clique em "Authorize" (ícone de cadeado)
4. Cole o token no campo "Value"
5. Clique em "Authorize"

O token será incluído automaticamente em todas as requisições protegidas.

## Schemas Definidos

O schema `Recipe` foi definido no `Controller.php` base e é reutilizado em várias rotas. Ele inclui:

- Informações básicas da receita (nome, tempo de preparo, porções)
- Dados do usuário e categoria
- Comentários e avaliações
- Contadores e médias

## Endpoints Documentados

### Autenticação
- `POST /api/register` - Registrar novo usuário
- `POST /api/login` - Fazer login
- `GET /api/user` - Obter usuário autenticado
- `POST /api/logout` - Fazer logout

### Categorias
- `GET /api/categories` - Listar todas as categorias

### Receitas Públicas
- `GET /api/public/recipes` - Listar receitas públicas (com filtros opcionais)
- `GET /api/public/recipes/{id}` - Obter receita pública específica

### Receitas (Protegidas)
- `GET /api/recipes` - Listar receitas (com filtros)
- `POST /api/recipes` - Criar nova receita
- `GET /api/recipes/{id}` - Obter receita específica
- `PUT /api/recipes/{id}` - Atualizar receita
- `DELETE /api/recipes/{id}` - Deletar receita
- `POST /api/recipes/scrape` - Fazer scraping de receita do TudoGostoso

### Comentários (Protegidos)
- `POST /api/public/recipes/{recipeId}/comments` - Criar comentário
- `DELETE /api/public/recipes/{recipeId}/comments/{commentId}` - Deletar comentário

### Avaliações (Protegidas)
- `POST /api/public/recipes/{recipeId}/ratings` - Criar/atualizar avaliação
- `GET /api/public/recipes/{recipeId}/ratings` - Obter avaliação do usuário

## Filtros Disponíveis

Os endpoints de listagem de receitas suportam os seguintes filtros:

- `category_id`: Filtrar por categoria
- `servings_operator` + `servings_value`: Filtrar por número de porções (exact, above, below)
- `prep_time_operator` + `prep_time_value`: Filtrar por tempo de preparo (exact, above, below)
- `rating_operator` + `rating_value`: Filtrar por avaliação média (exact, above, below)
- `comments_operator` + `comments_value`: Filtrar por número de comentários (exact, above, below)
- `my_recipes`: Filtrar apenas receitas do usuário autenticado (true/false)
- `search`: Buscar por texto (nome, ingredientes, instruções)
- `page`: Número da página para paginação

## Testando a API

O Swagger UI permite testar todos os endpoints diretamente:

1. Acesse a documentação em `/api/documentation`
2. Expanda o endpoint desejado
3. Clique em "Try it out"
4. Preencha os parâmetros necessários
5. Clique em "Execute"
6. Veja a resposta da API

## Notas Importantes

- A documentação é gerada automaticamente a partir das anotações nos controllers
- Sempre execute `php artisan l5-swagger:generate` após modificar anotações
- Os arquivos gerados são salvos em `storage/api-docs/`
- O formato padrão é JSON (`api-docs.json`)

