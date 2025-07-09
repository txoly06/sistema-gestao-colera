# Guia de Autenticação - Sistema de Gestão e Monitoramento de Casos de Cólera

Este documento descreve como autenticar-se na API do Sistema de Gestão e Monitoramento de Casos de Cólera usando Laravel Sanctum.

## Visão Geral

O sistema utiliza autenticação baseada em tokens (Laravel Sanctum) para proteger os recursos da API. O fluxo de autenticação segue estes passos:

1. O utilizador faz login com credenciais válidas
2. O servidor valida as credenciais e retorna um token de acesso
3. O cliente utiliza esse token em requisições subsequentes
4. Para encerrar a sessão, o utilizador pode revogar o token fazendo logout

## Utilizadores de Teste

O sistema inclui utilizadores de teste pré-configurados que podem ser usados para testar a API:

| Utilizador | Email | Password | Papel |
|------------|-------|----------|-------|
| Admin Sistema | admin@sistema-colera.ao | password123 | admin |
| Gestor Provincial | gestor@sistema-colera.ao | password123 | gestor |
| Dr. Carlos Santos | medico@sistema-colera.ao | password123 | medico |
| Enf. Maria Joaquina | enfermeiro@sistema-colera.ao | password123 | medico |
| João Motorista | motorista@sistema-colera.ao | password123 | motorista |

## Como Autenticar via Swagger UI

### 1. Obter Token de Acesso:

1. Na interface Swagger UI, navegue até a seção **Autenticação**
2. Localize o endpoint `/login` e clique para expandir
3. Clique no botão **Try it out**
4. Preencha os dados:
   ```json
   {
     "email": "admin@sistema-colera.ao",
     "password": "password123",
     "device_name": "Swagger UI"
   }
   ```
5. Clique em **Execute**
6. Na resposta, copie o token de acesso (campo `data.token`)

### 2. Configurar Token para Requisições:

1. No topo da página Swagger UI, clique no botão **Authorize**
2. No campo de token, digite `Bearer ` seguido do token copiado (ex: `Bearer 1|laravel_sanctum_TOKEN_AQUI`)
3. Clique em **Authorize**
4. Clique em **Close**

Agora todas as requisições que exigem autenticação usarão esse token automaticamente.

### 3. Verificar Autenticação:

1. Para confirmar que você está autenticado, teste o endpoint `/user` na seção Autenticação
2. Este endpoint retorna os detalhes do utilizador atual, incluindo permissões e papéis

### 4. Revogar Token (Logout):

1. Para encerrar a sessão, use o endpoint `/logout`
2. Após o logout, o token será invalidado e não poderá mais ser usado

## Como Autenticar via API Client (Postman, cURL, etc.)

### Login (Obter Token):

```bash
curl -X POST https://seu-dominio.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@sistema-colera.ao", "password": "password123", "device_name": "API Test"}'
```

### Usar o Token em Requisições:

```bash
curl https://seu-dominio.com/api/v1/pacientes \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

### Logout (Revogar Token):

```bash
curl -X POST https://seu-dominio.com/api/v1/logout \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

## Permissões e Papéis

O sistema utiliza o pacote Spatie Laravel-Permission para gerenciar permissões e papéis:

- **Papéis disponíveis**: admin, gestor, medico, enfermeiro, motorista
- **Permissões**: cada papel tem conjuntos específicos de permissões (ver, criar, editar, eliminar) para recursos do sistema

## Solução de Problemas

### Erros Comuns:

- **401 Unauthorized**: Verifique se o token está correto e não expirou
- **403 Forbidden**: O utilizador autenticado não tem permissão para acessar o recurso
- **429 Too Many Requests**: Limite de requisições excedido (rate limiting)

### Lembre-se:

- Os tokens Sanctum são de longa duração e não expiram automaticamente
- Para mudar de usuário, faça logout e obtenha um novo token com credenciais diferentes
- Proteja seus tokens e nunca os exponha em código front-end público
