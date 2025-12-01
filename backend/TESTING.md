# Guia de Testes - Backend

Este projeto utiliza **Pest** para testes unitários e de integração.

## Estrutura de Testes

```
tests/
├── Unit/              # Testes unitários
│   └── Services/      # Testes de serviços
├── Feature/           # Testes de integração/API
└── Pest.php           # Configuração do Pest
```

## Executando Testes

### Todos os testes
```bash
php artisan test
# ou
composer test
```

### Apenas testes unitários
```bash
php artisan test --testsuite=Unit
```

### Apenas testes de integração
```bash
php artisan test --testsuite=Feature
```

### Um arquivo específico
```bash
php artisan test tests/Unit/Services/AuthServiceTest.php
```

### Com cobertura
```bash
php artisan test --coverage
```

## Testes Implementados

### Testes Unitários (Services)

- **AuthServiceTest**: Testa registro, login, criação de token e logout
- **CategoryServiceTest**: Testa listagem de categorias
- **RecipeCommentServiceTest**: Testa criação, busca e deleção de comentários
- **RecipeRatingServiceTest**: Testa criação, atualização e busca de avaliações

### Testes de Integração (Feature)

- **AuthTest**: Testa endpoints de autenticação (register, login, logout, user)
- **CategoryTest**: Testa endpoint de listagem de categorias
- **RecipeTest**: Testa CRUD completo de receitas
- **RecipeCommentTest**: Testa criação e deleção de comentários
- **RecipeRatingTest**: Testa criação e busca de avaliações

## Escrevendo Novos Testes

### Exemplo de Teste Unitário

```php
<?php

use App\Services\MyService;

test('can do something', function () {
    $service = new MyService();
    $result = $service->doSomething();
    
    expect($result)->toBeTrue();
});
```

### Exemplo de Teste de Integração

```php
<?php

test('can create resource via API', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/resource', [
            'name' => 'Test Resource',
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('resources', ['name' => 'Test Resource']);
});
```

## Factories

O projeto utiliza factories do Laravel para criar dados de teste:

- `UserFactory`
- `CategoryFactory`
- `RecipeFactory`
- `RecipeCommentFactory`
- `RecipeRatingFactory`

## Configuração

Os testes utilizam:
- Banco de dados SQLite em memória (`:memory:`)
- RefreshDatabase para limpar dados entre testes
- Faker para dados aleatórios

## Boas Práticas

1. Use factories ao invés de criar modelos manualmente
2. Teste casos de sucesso e falha
3. Teste validações e regras de negócio
4. Use nomes descritivos para os testes
5. Mantenha os testes isolados e independentes

