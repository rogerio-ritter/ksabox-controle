# Componentes e Módulos

## includes/ — Core

### `includes/config.php`
Carrega o `.env` e define constantes globais. Deve ser o primeiro `require` em qualquer arquivo.

**Constantes definidas:** `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `APP_SECRET`, `APP_URL`

---

### `includes/db.php`
Singleton de conexão PDO.

```php
db(): PDO          // Retorna a instância única do PDO
DB::get(): PDO     // Equivalente (método estático da classe DB)
```

**Uso:**
```php
$stmt = db()->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch();
```

---

### `includes/auth.php`
Gerencia autenticação baseada em sessão.

```php
requireLogin(): void          // Redireciona para login se não autenticado
isLoggedIn(): bool            // Verifica se há sessão ativa
login(string $email, string $senha): bool  // Autentica e inicia sessão
logout(): void                // Destroi sessão
currentUser(): array          // Retorna ['id', 'nome', 'email', 'tema']
isDark(): bool                // true se tema do usuário for 'escuro'
```

---

### `includes/functions.php`
Utilitários gerais usados em toda a aplicação.

```php
h(mixed $v): string               // htmlspecialchars() — usar em TODA saída HTML
moneyBr(mixed $v): string         // "R$ 1.234,56" — formatar valores monetários
dateBr(?string $d): string        // "d/m/Y" — formatar datas para exibição
jsonResponse(bool $s, string $msg, mixed $data, int $code): never
                                  // Termina execução com JSON + header correto
getInput(): array                 // Lê POST form ou corpo JSON — usar nas api.php
activeMenu(string $segment): string // CSS classes para item ativo no menu
getEmpresa(): array               // Dados da empresa (cached em $_SESSION)
```

---

## layout/ — Estrutura de Página

### `layout/header.php`
Abre o documento HTML, inclui Tailwind CSS, aplica tema dark/light e renderiza navbar + sidebar.

- Aplica classe `dark` no `<html>` se `isDark()` for true
- Renderiza nome do usuário logado e links de navegação
- **Requer:** `$pageTitle` definida antes do include

### `layout/footer.php`
Fecha o HTML e define funções JavaScript globais disponíveis em todos os módulos.

**Funções JS globais:**
```javascript
showToast(msg, type)       // type: 'success' | 'error' | 'info'
openModal(id)              // Abre modal por ID
closeModal(id)             // Fecha modal por ID
confirmAction(msg, cb)     // Dialog de confirmação; cb() chamado se confirmado
toggleTheme()              // Alterna dark/light + POST /perfil/api.php
toggleSidebar()            // Toggle menu mobile
```

---

## Módulos de Negócio

Cada módulo segue o padrão: `index.php` (UI) + `api.php` (dados).

### `clientes/`
Gerencia clientes. UI com modal de criação/edição.

**Campos:** nome, email, telefone, cpf_cnpj, cidade, estado (2 chars), endereco, ativo

**JS em index.php:**
```javascript
abrirModal()              // Abre modal em branco (novo)
editarCliente(obj)        // Popula modal com dados do cliente
salvarCliente(e)          // POST /clientes/api.php
deletarCliente(id, nome)  // Confirm + POST delete
```

---

### `categorias/`
Gerencia categorias de produtos.

**Campos:** nome, descricao, ativo

**JS em index.php:**
```javascript
abrirModal()
editarCategoria(obj)
salvarCategoria(e)
deletarCategoria(id, nome)
```

---

### `produtos/`
Gerencia produtos. Associados a uma categoria.

**Campos:** nome, categoria_id, unidade (padrão='un'), descricao, ativo

**JS em index.php:**
```javascript
abrirModal()
editarProduto(obj)
salvarProduto(e)
deletarProduto(id, nome)
```

---

### `tabela_preco/`
Gerencia listas de preços. Cada tabela contém N itens (produto + preço).

**Campos tabela:** nome, descricao, ativo
**Campos item:** tabela_id, produto_id, preco

**JS em index.php:**
```javascript
abrirModalTabela()
editarTabela(obj)
salvarTabela(e)
deletarTabela(id, nome)
gerenciarItens(id, nome)   // Abre modal de gerenciamento de itens
carregarItens()            // GET api.php?action=itens
adicionarItem()            // POST add_item
atualizarPreco(itemId, preco) // POST update_item
removerItem(itemId)        // POST delete_item
```

---

### `orcamentos/`
Módulo principal. Três páginas + API.

**`orcamentos/index.php`** — Lista com filtro por status (tabs: Todos, Rascunho, Enviado, Aprovado, Rejeitado, Cancelado)

**`orcamentos/form.php`** — Editor com:
- Cabeçalho: número, cliente, tabela de preço, datas, status, observações
- Tabela de itens: adicionar/remover linhas, lookup de preço por produto

**`orcamentos/print.php`** — Layout de impressão com dados da empresa e itens formatados

**JS em form.php:**
```javascript
adicionarLinha(item)              // Adiciona linha à tabela de itens
produtoSelecionado(idx, sel)      // Atualiza preço ao selecionar produto
atualizarLinha(idx, campo, val)   // Recalcula total da linha
removerLinha(idx)                 // Remove linha
recalcTotal()                     // Soma todos os totais
atualizarPrecos()                 // Atualiza preços via tabela selecionada
salvarOrcamento(e)                // POST /orcamentos/api.php
```

---

### `empresa/index.php`
Formulário simples para dados da empresa (nome, cnpj, telefone, email, endereço). Usado na impressão de orçamentos.

---

### `perfil/`
Perfil do usuário: dados pessoais, senha e tema.

**`perfil/api.php`** — Única action: `update_tema` (POST com campo `tema`)

---

## dashboard.php

Agrega métricas de todas as tabelas e renderiza gráficos com Chart.js:
- Contadores: clientes ativos, produtos ativos, categorias, total de orçamentos, total aprovado (R$)
- Gráfico de linha: orçamentos nos últimos 6 meses
- Gráfico de rosca: distribuição por status
- Tabela: últimos 10 orçamentos
