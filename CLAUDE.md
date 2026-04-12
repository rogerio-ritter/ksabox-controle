# CLAUDE.md — Sistema de Orçamentos

## Visão Geral

Sistema web para criação e gerenciamento de orçamentos comerciais. Permite cadastrar clientes, produtos, categorias e tabelas de preços para gerar orçamentos com múltiplos itens, controle de status e impressão.

**Stack:** PHP 8+, MySQL (PDO), Tailwind CSS (CDN), Chart.js, JavaScript puro (sem framework)
**Servidor local:** WampServer — acessível em `http://localhost/padrao-claude`

## Estrutura de Diretórios

```
padrao-claude/
├── .env                    # Credenciais de banco e configurações (NÃO commitar)
├── index.php               # Roteador: redireciona p/ login ou dashboard
├── login.php               # Autenticação
├── logout.php              # Encerra sessão
├── dashboard.php           # Painel com estatísticas e gráficos
├── setup.php               # Inicializa banco de dados (executar 1x, depois deletar)
├── includes/
│   ├── config.php          # Carrega .env e define constantes
│   ├── db.php              # Singleton PDO — função db()
│   ├── auth.php            # Funções de sessão/login
│   └── functions.php       # Utilitários: h(), moneyBr(), jsonResponse()
├── layout/
│   ├── header.php          # HTML head + navbar + sidebar
│   ├── sidebar.php         # Menu lateral de navegação
│   └── footer.php          # Fechamento HTML + JS global (toast, modal)
├── clientes/               # Módulo de clientes (index.php + api.php)
├── categorias/             # Módulo de categorias (index.php + api.php)
├── produtos/               # Módulo de produtos (index.php + api.php)
├── tabela_preco/           # Módulo de tabelas de preços (index.php + api.php)
├── orcamentos/             # Módulo de orçamentos (index.php, form.php, print.php, api.php)
├── empresa/                # Dados da empresa (index.php)
├── perfil/                 # Perfil do usuário e tema (index.php + api.php)
└── docs/                   # Documentação técnica do projeto
```

## Comandos Essenciais

```bash
# Configurar banco (primeira vez)
# Acessar no browser: http://localhost/padrao-claude/setup.php
# Após rodar, deletar o arquivo setup.php

# Não há build, compilação ou gerenciador de pacotes
# Tailwind CSS e Chart.js são carregados via CDN no layout/header.php
```

## Como Navegar Nesta Documentação

| Tarefa | Arquivo |
|--------|---------|
| Entender a arquitetura geral | [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) |
| Ver módulos e suas funções PHP/JS | [docs/COMPONENTS.md](docs/COMPONENTS.md) |
| Ver schema do banco de dados | [docs/DATA_MODELS.md](docs/DATA_MODELS.md) |
| Ver endpoints de API | [docs/API.md](docs/API.md) |

## Convenções de Código

- **Autenticação:** Toda página (exceto `login.php`) chama `requireLogin()` no topo
- **Acesso ao banco:** Sempre via `db()` (singleton PDO) com prepared statements
- **APIs:** Cada módulo tem `api.php` que retorna JSON via `jsonResponse()`; recebe input via `getInput()` (suporta POST form e JSON body)
- **Saída HTML:** Todo dado do usuário/banco é escapado com `h($valor)` antes de imprimir
- **Dinheiro:** Sempre formatar com `moneyBr($valor)` → "R$ 1.234,56"
- **Datas:** Banco armazena `Y-m-d`, exibir com `dateBr($data)` → "d/m/Y"
- **Padrão de módulo:** `index.php` = UI + JavaScript; `api.php` = lógica de dados
- **Tema:** `isDark()` retorna bool; Tailwind dark mode via classe `dark` no `<html>`

## Variáveis de Ambiente (`.env`)

```ini
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=padrao_claude
APP_SECRET=padrao_claude_secret_key_2024
APP_URL=http://localhost/padrao-claude
```

## Credenciais Padrão (após setup.php)

- **Email:** `admin@admin.com`
- **Senha:** `admin123`

## Segurança

- Inputs escapados com `h()` (XSS)
- Queries com prepared statements (SQL injection)
- Senhas com `password_hash()` / `password_verify()`
- APIs aceitam apenas POST para operações de escrita
- Chaves estrangeiras no banco previnem registros órfãos
