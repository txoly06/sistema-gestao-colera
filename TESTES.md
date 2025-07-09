# Documentação de Testes - Sistema de Gestão de Cólera

## 1. Correções e Implementações Realizadas

### 1.1 Correção no Sistema de Filtros de Triagem

O método `index` do `TriagemController` foi aprimorado para:
- Verificar se os valores dos filtros são vazios antes de aplicá-los
- Incluir meta-informações de debug na resposta (contagem total sem filtros, com filtros, filtros aplicados)
- Melhorar a detecção dos filtros vazios para evitar retornos em branco incorretos

### 1.2 Implementação do Sistema de Encaminhamento

Foi implementado o sistema completo de encaminhamento, incluindo:
- Modelo `Encaminhamento` com relacionamentos, scopes e configurações
- Controlador `EncaminhamentoController` com métodos CRUD e especializados
- Rotas de API para todas as operações do sistema de encaminhamento
- Migração para a tabela de encaminhamentos
- Integração com triagem, pacientes, unidades de saúde e veículos

## 2. Testes de API

### 2.1 Testando o Sistema de Filtros de Triagem

#### Endpoint para Listar Triagens com Filtros

```http
GET /api/v1/triagens?status=em_atendimento&nivel_urgencia=alta
```

**Parâmetros disponíveis:**
- `status`: pendente, em_atendimento, encaminhada, concluida, cancelada
- `nivel_urgencia`: baixa, media, alta, emergencia
- `unidade_saude_id`: ID da unidade de saúde
- `ponto_cuidado_id`: ID do ponto de cuidado
- `min_probabilidade_colera`: valor mínimo de probabilidade (0-100)

**Resposta:**
Além das triagens filtradas, agora retorna meta-informações:

```json
{
  "success": true,
  "message": "Triagens obtidas com sucesso - Filtros aplicados: 2",
  "data": {
    "current_page": 1,
    "data": [... triagens ...],
    "meta_info": {
      "filtros_aplicados": {
        "status": "em_atendimento",
        "nivel_urgencia": "alta"
      },
      "total_registros_sem_filtro": 42,
      "total_com_filtros": 5
    }
  }
}
```

### 2.2 Testando o Sistema de Encaminhamento

#### 2.2.1 Listar Encaminhamentos

```http
GET /api/v1/encaminhamentos
```

**Parâmetros de filtro opcionais:**
- `status`: pendente, aprovado, em_transporte, concluido, cancelado
- `prioridade`: baixa, media, alta, emergencia
- `unidade_origem_id`: ID da unidade de origem
- `unidade_destino_id`: ID da unidade de destino
- `veiculo_id`: ID do veículo

#### 2.2.2 Obter Detalhe de Encaminhamento

```http
GET /api/v1/encaminhamentos/{id}
```

#### 2.2.3 Criar Novo Encaminhamento

```http
POST /api/v1/encaminhamentos
```

**Corpo da requisição (JSON):**
```json
{
  "paciente_id": 1,
  "triagem_id": 5,
  "unidade_origem_id": 2,
  "unidade_destino_id": 3,
  "motivo": "Necessidade de atendimento especializado",
  "prioridade": "alta",
  "observacoes": "Paciente com desidratação severa",
  "recursos_necessarios": {
    "oxigenio": true,
    "equipamento_especial": "ventilador"
  }
}
```

#### 2.2.4 Atualizar Encaminhamento

```http
PUT /api/v1/encaminhamentos/{id}
```

**Corpo da requisição (JSON):**
```json
{
  "unidade_destino_id": 4,
  "prioridade": "emergencia",
  "observacoes": "Paciente apresentou piora no quadro"
}
```

#### 2.2.5 Excluir Encaminhamento

```http
DELETE /api/v1/encaminhamentos/{id}
```

#### 2.2.6 Listar Encaminhamentos Pendentes

```http
GET /api/v1/encaminhamentos/pendentes
```

**Parâmetros de filtro opcionais:**
- `prioridade`: baixa, media, alta, emergencia
- `unidade_destino_id`: ID da unidade de destino

#### 2.2.7 Atualizar Status de Encaminhamento

```http
PUT /api/v1/encaminhamentos/{id}/status
```

**Corpo da requisição (JSON):**
```json
{
  "status": "em_transporte",
  "observacao": "Paciente em transporte com acompanhante",
  "data_inicio_transporte": "2025-06-28T14:30:00"
}
```

**Estados possíveis para transição:**
- pendente → aprovado, cancelado
- aprovado → em_transporte, cancelado
- em_transporte → concluido, cancelado
- concluido → (estado final)
- cancelado → (estado final)

#### 2.2.8 Atribuir Veículo ao Encaminhamento

```http
PUT /api/v1/encaminhamentos/{id}/atribuir-veiculo
```

**Corpo da requisição (JSON):**
```json
{
  "veiculo_id": 5,
  "previsao_partida": "2025-06-28T14:00:00",
  "previsao_chegada": "2025-06-28T15:30:00",
  "observacao": "Veículo equipado com suporte avançado"
}
```

## 3. Fluxo Completo de Teste

1. **Criar um encaminhamento** a partir de uma triagem existente
2. **Verificar o encaminhamento** na lista de encaminhamentos pendentes
3. **Atribuir um veículo** ao encaminhamento
4. **Atualizar o status** para "em_transporte"
5. **Verificar que o status da triagem** foi atualizado para "encaminhada"
6. **Concluir o encaminhamento** alterando o status para "concluido"
7. **Verificar que a triagem** foi marcada como "concluida"

## 4. Notas Importantes

- A atribuição de veículo só é possível para encaminhamentos com status "pendente" ou "aprovado"
- O veículo deve estar com status "disponível" para ser atribuído
- Ao atribuir um veículo, seu status é alterado para "designado"
- As transições de estado do encaminhamento seguem um fluxo específico
- Encaminhamentos "concluídos" ou "cancelados" não podem ser modificados
- A exclusão de um encaminhamento é uma operação de soft delete

## 5. Permissões Necessárias

Para acessar os endpoints, o usuário deve ter as permissões:
- `ver encaminhamentos`
- `criar encaminhamentos`
- `editar encaminhamentos`
- `eliminar encaminhamentos`
