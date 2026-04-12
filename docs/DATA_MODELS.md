# Modelos de Dados

**Banco:** `padrao_claude` | **Engine:** InnoDB | **Charset:** utf8mb4_unicode_ci

## Diagrama de Relacionamentos

```
usuarios ──────────────────────── orcamentos
                                      │
empresa (1 registro apenas)           │
                                      │
categorias ──── produtos ─────────── orcamento_itens
                    │
                tabela_preco_itens ── tabelas_precos ── orcamentos
                    │
clientes ─────────────────────────── orcamentos
```

```
clientes       1 ──── N  orcamentos
usuarios       1 ──── N  orcamentos
tabelas_precos 1 ──── N  orcamentos
tabelas_precos 1 ──── N  tabela_preco_itens
produtos       1 ──── N  tabela_preco_itens
orcamentos     1 ──── N  orcamento_itens
produtos       1 ──── N  orcamento_itens
categorias     1 ──── N  produtos
```

---

## Tabelas

### `usuarios`
Usuários do sistema com suporte a tema dark/light.

| Campo | Tipo | Obrigatório | Padrão | Descrição |
|-------|------|-------------|--------|-----------|
| id | INT PK AUTO | — | — | Chave primária |
| nome | VARCHAR(100) | SIM | — | Nome do usuário |
| email | VARCHAR(100) UNIQUE | SIM | — | Login |
| senha | VARCHAR(255) | SIM | — | Hash bcrypt |
| tema | ENUM('claro','escuro') | — | 'claro' | Preferência de tema |
| ativo | TINYINT(1) | — | 1 | 0=inativo |
| created_at | TIMESTAMP | — | NOW() | — |

**Regras:** email único; senha sempre armazenada como hash (nunca texto puro)

---

### `empresa`
Dados da empresa emitente. Tabela com **um único registro** (id=1).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT PK | Sempre 1 |
| nome | VARCHAR(200) | Razão social |
| cnpj | VARCHAR(20) | CNPJ formatado |
| endereco | TEXT | Endereço completo |
| telefone | VARCHAR(20) | — |
| email | VARCHAR(100) | — |
| updated_at | TIMESTAMP | Atualização automática |

---

### `categorias`
Agrupamento de produtos.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| nome | VARCHAR(100) | SIM | Nome da categoria |
| descricao | TEXT | — | Descrição opcional |
| ativo | TINYINT(1) | — | Padrão 1 |
| created_at | TIMESTAMP | — | — |

**Regra:** Não pode ser deletada se tiver produtos vinculados (validado na API).

---

### `produtos`
Catálogo de produtos/serviços. Sem preço — preço fica em `tabela_preco_itens`.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| categoria_id | INT FK | — | NULL se categoria deletada |
| nome | VARCHAR(200) | SIM | — |
| descricao | TEXT | — | — |
| unidade | VARCHAR(20) | — | Padrão 'un' (ex: m², kg, h) |
| ativo | TINYINT(1) | — | Padrão 1 |
| created_at | TIMESTAMP | — | — |

**FK:** `categoria_id` → `categorias.id` ON DELETE SET NULL

---

### `clientes`
Compradores dos orçamentos.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| nome | VARCHAR(200) | SIM | — |
| email | VARCHAR(100) | — | — |
| telefone | VARCHAR(20) | — | — |
| cpf_cnpj | VARCHAR(20) | — | CPF ou CNPJ (sem máscara obrigatória) |
| endereco | TEXT | — | — |
| cidade | VARCHAR(100) | — | — |
| estado | VARCHAR(2) | — | UF (2 letras) |
| ativo | TINYINT(1) | — | Padrão 1 |
| created_at | TIMESTAMP | — | — |

**Regra:** Não pode ser deletado se tiver orçamentos vinculados.

---

### `tabelas_precos`
Listas de preço. Um orçamento pode usar uma tabela específica.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| nome | VARCHAR(100) | SIM | Ex: "Tabela Varejo", "Tabela Atacado" |
| descricao | TEXT | — | — |
| ativo | TINYINT(1) | — | Padrão 1 |
| created_at | TIMESTAMP | — | — |

---

### `tabela_preco_itens`
Preço de cada produto em cada tabela. Um produto pode ter preço diferente em cada tabela.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| tabela_id | INT FK | SIM | Tabela de preço |
| produto_id | INT FK | SIM | Produto |
| preco | DECIMAL(10,2) | SIM | Preço unitário |

**Constraint:** UNIQUE (tabela_id, produto_id) — um produto aparece 1x por tabela
**FK:** ON DELETE CASCADE em ambas as FKs

---

### `orcamentos`
Documento principal de orçamento.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| cliente_id | INT FK | — | NULL se cliente deletado |
| usuario_id | INT FK | — | Quem criou |
| tabela_id | INT FK | — | Tabela de preço usada |
| numero | VARCHAR(20) | SIM | Ex: "ORC-2024-0001" |
| status | ENUM | — | Ver valores abaixo |
| data_criacao | DATE | — | Data do orçamento |
| data_validade | DATE | — | Validade da proposta |
| observacoes | TEXT | — | — |
| total | DECIMAL(10,2) | — | Calculado automaticamente |
| created_at | TIMESTAMP | — | — |
| updated_at | TIMESTAMP | — | Atualização automática |

**Status possíveis:** `rascunho` | `enviado` | `aprovado` | `rejeitado` | `cancelado`

**Regra:** `total` é recalculado via `SUM(orcamento_itens.total)` a cada save.

**Formato do número:** `ORC-{YYYY}-{NNNN}` gerado automaticamente se não informado.

---

### `orcamento_itens`
Linhas do orçamento. Podem ou não estar vinculadas a um produto cadastrado.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | INT PK AUTO | — | — |
| orcamento_id | INT FK | SIM | Orçamento pai |
| produto_id | INT FK | — | NULL = item livre (sem produto) |
| descricao | VARCHAR(255) | SIM | Descrição da linha |
| quantidade | DECIMAL(10,2) | SIM | Padrão 1.00 |
| preco_unitario | DECIMAL(10,2) | SIM | Padrão 0.00 |
| total | DECIMAL(10,2) | SIM | quantidade × preco_unitario |

**FK:** `orcamento_id` ON DELETE CASCADE (itens removidos com o orçamento)
**FK:** `produto_id` ON DELETE SET NULL (item fica como livre se produto for deletado)

---

## Regras de Negócio nos Dados

- **Soft delete:** Registros não são deletados fisicamente, apenas `ativo=0` — exceto itens de orçamento (hard delete cascadeado)
- **Dependências bloqueiam delete:** categorias com produtos, clientes com orçamentos
- **Preço desacoplado do produto:** produto não tem preço; preço fica na `tabela_preco_itens`, permitindo múltiplas listas de preço
- **Total calculado no backend:** nunca confiar no total enviado pelo frontend; sempre recalcular no `api.php`
- **Item livre:** `orcamento_itens.produto_id` pode ser NULL; `descricao` é o campo obrigatório nesses casos
