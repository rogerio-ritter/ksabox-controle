# Arquitetura — Sistema de Orçamentos

## Diagrama Textual

```
Browser
  │
  ├─ GET /index.php ──────────────► Redireciona para /login.php ou /dashboard.php
  │
  ├─ GET /[modulo]/index.php ─────► PHP renderiza HTML completo (server-side)
  │      │                              inclui layout/header.php
  │      │                              inclui layout/sidebar.php
  │      │                              inclui layout/footer.php (JS global)
  │      │
  │      └─ JS do módulo faz ──────► POST/GET /[modulo]/api.php
  │                                        │
  │                                        └─► includes/db.php (PDO singleton)
  │                                                  │
  │                                                  └─► MySQL: padrao_claude
  │
  └─ GET /orcamentos/print.php ───► HTML otimizado para impressão/PDF
```

## Camadas da Aplicação

```
┌─────────────────────────────────────┐
│  Apresentação (layout/)             │  header.php, sidebar.php, footer.php
├─────────────────────────────────────┤
│  Módulos UI ([modulo]/index.php)    │  HTML + JavaScript inline (AJAX)
├─────────────────────────────────────┤
│  APIs JSON ([modulo]/api.php)       │  Recebem POST/GET, retornam JSON
├─────────────────────────────────────┤
│  Core (includes/)                   │  config, db, auth, functions
├─────────────────────────────────────┤
│  Banco de Dados (MySQL)             │  PDO com prepared statements
└─────────────────────────────────────┘
```

## Módulos e Dependências

```
dashboard.php
  └─► clientes, produtos, categorias, orcamentos (leitura de stats)

orcamentos/form.php
  ├─► clientes (select de cliente)
  ├─► tabela_preco (lookup de preços)
  └─► produtos (select de produto por linha)

tabela_preco/index.php
  └─► produtos (itens da tabela referenciram produtos)

produtos/index.php
  └─► categorias (produto pertence a uma categoria)
```

## Fluxo de Dados — Criação de Orçamento

```
1. Usuário abre /orcamentos/form.php
   └─► PHP carrega clientes, tabelas de preço, produtos do banco

2. Usuário seleciona tabela de preço
   └─► JS chama GET /tabela_preco/api.php?action=itens&tabela_id=X
       └─► Retorna preços dos produtos → JS atualiza campos de preço

3. Usuário seleciona produto em uma linha
   └─► JS preenche preço_unitario a partir dos itens da tabela carregada

4. Usuário clica Salvar
   └─► JS monta objeto JSON com cabeçalho + array de itens
       └─► POST /orcamentos/api.php (action=create ou update)
           ├─► Valida campos obrigatórios
           ├─► INSERT/UPDATE orcamentos
           ├─► DELETE orcamento_itens (se update)
           ├─► INSERT orcamento_itens (loop nos itens)
           ├─► UPDATE orcamentos.total
           └─► Retorna {success, data: {id, numero}}
               └─► JS redireciona para /orcamentos/index.php
```

## Fluxo de Autenticação

```
Requisição qualquer página
  └─► requireLogin()
        ├─► isset($_SESSION['usuario_id']) ?
        │     SIM → continua
        │     NÃO → header('Location: /login.php') + exit
        │
login.php POST
  └─► login($email, $senha)
        ├─► SELECT usuario por email
        ├─► password_verify(senha, hash)
        ├─► $_SESSION['usuario_id'] = id
        ├─► $_SESSION['usuario_nome'] = nome
        ├─► $_SESSION['usuario_email'] = email
        └─► $_SESSION['usuario_tema'] = tema
```

## Decisões de Design

| Decisão | Motivo |
|---------|--------|
| PHP puro sem framework | Simplicidade, zero dependências, fácil deploy em qualquer servidor |
| JavaScript inline nos módulos | Evita complexidade de build; JS é simples e específico por módulo |
| PDO singleton (`db()`) | Garante uma única conexão por request; fácil de usar em qualquer arquivo |
| APIs JSON separadas (`api.php`) | Permite chamadas AJAX sem recarregar página; reutilizável |
| Tailwind via CDN | Sem etapa de build; suficiente para projeto deste porte |
| Tema dark/light salvo no banco | Persiste entre dispositivos/sessões diferentes do mesmo usuário |
| Tabelas de preço desacopladas | Permite múltiplas listas de preço sem alterar cadastro de produtos |

## Dependências Externas

| Dependência | Versão | Uso | Carregamento |
|-------------|--------|-----|--------------|
| Tailwind CSS | 3.x (CDN) | Estilização completa | `<script src="cdn.tailwindcss.com">` |
| Chart.js | CDN | Gráficos do dashboard | `<script src="cdn.jsdelivr.net">` |
| PHP | 8.0+ | Backend | Servidor |
| MySQL | 5.7+ / 8.x | Banco de dados | Servidor |

Sem Composer, sem npm, sem nenhum gerenciador de pacotes.
