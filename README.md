# Sistema de Gestão e Monitoramento de Casos de Cólera

<p align="center">
<img src="https://img.shields.io/badge/laravel-10.0%2B-red" alt="Laravel 10+">
<img src="https://img.shields.io/badge/php-8.2%2B-blue" alt="PHP 8.2+">
<img src="https://img.shields.io/badge/status-em%20desenvolvimento-yellow" alt="Em desenvolvimento">
<img src="https://img.shields.io/badge/licença-MIT-green" alt="Licença MIT">
</p>

## Visão Geral

O Sistema de Gestão e Monitoramento de Casos de Cólera é uma aplicação web desenvolvida com Laravel 10+, projetada para auxiliar no controle, rastreamento e gestão de surtos de cólera. Este sistema interliga unidades de saúde, pontos de cuidado e veículos de emergência, permitindo uma resposta rápida e eficiente durante crises sanitárias.

## Funcionalidades Principais

### Implementado

- 🏛️ **Gabinetes Provinciais**: Gestão de autoridades sanitárias por província
- 🏥 **Unidades de Saúde**: Mapeamento e gerenciamento de unidades de atendimento
- 👨‍⚕️ **Pacientes**: Registro e acompanhamento de pacientes infectados
- 🔍 **Pontos de Cuidado**: Estações temporárias de tratamento e prevenção
- 🚑 **Veículos (Ambulâncias)**: Monitoramento em tempo real de ambulâncias e veículos logísticos

### Em Desenvolvimento

- 🩺 **Triagem Inteligente**: Sistema de recomendação baseado em sintomas
- 🗺️ **Geolocalização**: Integração com Google Maps API para rotas e localização
- 📱 **QR Code**: Geração de QR Code para identificação rápida de pacientes
- 📊 **Dashboards e Relatórios**: Visualização interativa de dados e estatísticas
- 🔐 **Auditoria**: Log de atividades e controle de acesso

## Tecnologias Utilizadas

- **Backend**: Laravel 10+ (PHP 8.2+)
- **Autenticação**: Laravel Sanctum
- **Controle de Acesso**: Spatie Laravel Permission
- **Banco de Dados**: MySQL/SQLite (ambiente de testes)
- **Testes**: PHPUnit

## Requisitos de Sistema

- PHP 8.2 ou superior
- Composer
- MySQL 8.0 ou superior
- Node.js & NPM (para assets frontend)

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/sistema-gestao-colera.git
   cd sistema-gestao-colera
   ```

2. Instale as dependências:
   ```bash
   composer install
   npm install
   ```

3. Configure o ambiente:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure a conexão do banco de dados no arquivo `.env`

5. Execute as migrações:
   ```bash
   php artisan migrate --seed
   ```

6. Inicie o servidor:
   ```bash
   php artisan serve
   ```

## Estrutura do Projeto

```
├── app/                 # Código principal da aplicação
│   ├── Http/            # Controladores, Middleware, etc.
│   │   ├── Controllers/ # Controladores da aplicação
│   ├── Models/          # Modelos Eloquent
├── database/           
│   ├── migrations/      # Migrações de banco de dados
│   ├── seeders/        # Dados iniciais de teste
│   ├── factories/      # Factories para testes
├── routes/              # Definição de rotas
│   ├── api.php         # Rotas da API
│   ├── web.php         # Rotas web
├── tests/               # Testes automatizados
```

## Testes

O sistema inclui testes de integração completos. Para executar os testes:

```bash
php artisan test
```

Para testes específicos de módulos:

```bash
php artisan test tests/Feature/Api/V1/VeiculoTest.php
```

## API Endpoints

O sistema fornece uma API RESTful para integração com outras aplicações:

### Veículos
- `GET /api/v1/veiculos` - Listar todos os veículos
- `POST /api/v1/veiculos` - Criar novo veículo
- `GET /api/v1/veiculos/{id}` - Detalhes do veículo
- `PUT /api/v1/veiculos/{id}` - Atualizar veículo
- `DELETE /api/v1/veiculos/{id}` - Remover veículo
- `PUT /api/v1/veiculos/{id}/status` - Atualizar status
- `PUT /api/v1/veiculos/{id}/localizacao` - Atualizar localização
- `GET /api/v1/veiculos-disponiveis` - Listar veículos disponíveis
- `GET /api/v1/veiculos-por-tipo/{tipo}` - Filtrar por tipo

### Pontos de Cuidado
- `GET /api/v1/pontos-cuidado` - Listar pontos de cuidado
- `POST /api/v1/pontos-cuidado` - Criar ponto de cuidado
- `GET /api/v1/pontos-cuidado/{id}` - Detalhes do ponto de cuidado
- `PUT /api/v1/pontos-cuidado/{id}` - Atualizar ponto de cuidado
- `DELETE /api/v1/pontos-cuidado/{id}` - Remover ponto de cuidado

## Contribuição

Contribuições são bem-vindas! Por favor, siga os passos abaixo:

1. Fork o projeto
2. Crie uma branch para sua funcionalidade (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Autores

- Nome do Autor - [Email ou Perfil GitHub]

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

## Agradecimentos

- Laravel Team
- Spatie
- E todos os contribuidores de pacotes open-source utilizados
