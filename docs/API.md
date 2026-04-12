# API Reference

Todas as APIs retornam JSON. Formato padrão de resposta:

```json
{ "success": true|false, "message": "texto", "data": { ... } }
```

**Autenticação:** Sessão PHP (cookie). Todas as chamadas requerem usuário logado.
**Content-Type aceito:** `application/x-www-form-urlencoded` (form POST) ou `application/json`

---

## Clientes — `/clientes/api.php`

### Criar cliente
```
POST /clientes/api.php
action=create
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| nome | string | SIM |
| email | string | — |
| telefone | string | — |
| cpf_cnpj | string | — |
| cidade | string | — |
| estado | string (2) | — |
| endereco | string | — |
| ativo | int (0/1) | — |

**Resposta:** `{ success: true, message: "...", data: { id: N } }`

### Editar cliente
```
POST /clientes/api.php
action=update
```
Mesmos campos + `id` (obrigatório).

### Deletar cliente
```
POST /clientes/api.php
action=delete
```
| Campo | Obrigatório |
|-------|-------------|
| id | SIM |

**Erro:** Retorna `success: false` se cliente tiver orçamentos vinculados.

---

## Categorias — `/categorias/api.php`

### Criar / Editar
```
POST action=create|update
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| nome | string | SIM |
| descricao | string | — |
| ativo | int (0/1) | — |
| id | int | Só em update |

### Deletar
```
POST action=delete  { id }
```
**Erro:** Retorna `success: false` se tiver produtos vinculados.

---

## Produtos — `/produtos/api.php`

### Criar / Editar
```
POST action=create|update
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| nome | string | SIM |
| categoria_id | int | — |
| unidade | string | — (padrão 'un') |
| descricao | string | — |
| ativo | int (0/1) | — |
| id | int | Só em update |

### Deletar
```
POST action=delete  { id }
```

---

## Tabelas de Preço — `/tabela_preco/api.php`

### Criar / Editar tabela
```
POST action=create|update
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| nome | string | SIM |
| descricao | string | — |
| ativo | int (0/1) | — |
| id | int | Só em update |

### Deletar tabela
```
POST action=delete  { id }
```
Deleta em cascata todos os itens.

### Listar itens da tabela
```
GET /tabela_preco/api.php?action=itens&tabela_id=N
```
**Resposta:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "produto_id": 5, "produto_nome": "...", "unidade": "un", "preco": "150.00" }
  ]
}
```

### Adicionar / atualizar item (upsert)
```
POST action=add_item
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| tabela_id | int | SIM |
| produto_id | int | SIM |
| preco | decimal | SIM |

Se o par (tabela_id, produto_id) já existe, atualiza o preço.

### Atualizar preço de item existente
```
POST action=update_item  { id, preco }
```

### Remover item
```
POST action=delete_item  { id }
```

---

## Orçamentos — `/orcamentos/api.php`

### Criar orçamento
```
POST action=create
```
| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| numero | string | — (auto: ORC-YYYY-NNNN) |
| cliente_id | int | — |
| tabela_id | int | — |
| status | string | — (padrão 'rascunho') |
| data_criacao | string (Y-m-d) | — |
| data_validade | string (Y-m-d) | — |
| observacoes | string | — |
| itens | array | — |

**Estrutura de cada item:**
```json
{
  "produto_id": 5,
  "descricao": "Produto X",
  "quantidade": 2.00,
  "preco_unitario": 150.00,
  "total": 300.00
}
```
- `produto_id` pode ser `null` (item livre)
- `total` no backend é recalculado como `quantidade × preco_unitario`

**Resposta:** `{ success: true, data: { id: N, numero: "ORC-2024-0001" } }`

### Editar orçamento
```
POST action=update
```
Mesmos campos + `id` (obrigatório). Deleta todos os itens anteriores e reinserere os novos.

### Deletar orçamento
```
POST action=delete  { id }
```
Deleta em cascata todos os itens.

---

## Perfil — `/perfil/api.php`

### Atualizar tema
```
POST action=update_tema
```
| Campo | Valores | Obrigatório |
|-------|---------|-------------|
| tema | 'claro' \| 'escuro' | SIM |

Atualiza banco e `$_SESSION['usuario_tema']`.

**Resposta:** `{ success: true }`

---

## Empresa — `/empresa/index.php`

Formulário tradicional (POST sem AJAX). Não é uma API JSON.

Campos: `nome` (obrigatório), `cnpj`, `telefone`, `email`, `endereco`

---

## Tratamento de Erros

| Situação | HTTP Status | Corpo |
|----------|-------------|-------|
| Sucesso | 200 | `{ success: true, message: "...", data: {...} }` |
| Erro de validação / negócio | 200 | `{ success: false, message: "Descrição do erro" }` |
| Action inválida | 200 | `{ success: false, message: "Ação inválida" }` |

> As APIs não utilizam status HTTP 4xx/5xx — todos os erros retornam 200 com `success: false`.
