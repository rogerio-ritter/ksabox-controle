# Sistema de Orçamento e Controle de Estoque - Ksabox

## 📋 Visão Geral do Projeto

Sistema web para gestão de importação, precificação, estoque e orçamentos de produtos modulares (casas modulares, isopainel, pisos vinílicos, painéis SPC/WPC) importados da China.

### Stack Tecnológica
- **Backend:** PHP 8+ com PDO (MySQL)
- **Frontend:** Tailwind CSS (CDN), JavaScript vanilla
- **Gráficos:** Chart.js
- **Servidor:** WampServer
- **URL Base:** `http://localhost/ksabox-controle`

---

## 📁 Estrutura de Diretórios Completa

```
ksabox-controle/
├── .env                          # Credenciais (NÃO versionar)
├── .gitignore                    # Ignorar .env, logs, etc
├── index.php                     # Roteador principal
├── login.php                     # Página de autenticação
├── logout.php                    # Encerramento de sessão
├── dashboard.php                 # Dashboard principal
├── setup.php                     # Instalador do banco (deletar após uso)
│
├── assets/
│   ├── img/
│   │   ├── icone.png            # Favicon/ícone do sistema
│   │   └── logo.png             # Logo da Ksabox
│   └── js/
│       └── global.js            # Funções JS compartilhadas (toast, modal, etc)
│
├── includes/
│   ├── config.php               # Carrega .env e define constantes
│   ├── db.php                   # Singleton PDO - função db()
│   ├── auth.php                 # requireLogin(), login(), logout(), etc
│   └── functions.php            # Utilitários (h(), moneyBr(), dateBr(), jsonResponse(), getInput())
│
├── layout/
│   ├── header.php               # <head> + navbar + CDNs
│   ├── sidebar.php              # Menu lateral de navegação
│   └── footer.php               # Scripts globais + fechamento HTML
│
├── cadastros/
│   ├── categorias/
│   │   ├── index.php            # CRUD UI
│   │   └── api.php              # Endpoints JSON
│   ├── produtos/
│   │   ├── index.php
│   │   └── api.php
│   ├── clientes/
│   │   ├── index.php
│   │   └── api.php
│   ├── fornecedores/
│   │   ├── index.php
│   │   └── api.php
│   ├── unidades/
│   │   ├── index.php
│   │   └── api.php
│   ├── tabela_precos/
│   │   ├── index.php
│   │   └── api.php
│   └── usuarios/
│       ├── index.php
│       └── api.php
│
├── estoque/
│   ├── entrada/
│   │   ├── index.php
│   │   └── api.php
│   ├── saida/
│   │   ├── index.php
│   │   └── api.php
│   └── relatorio/
│       ├── index.php
│       └── api.php
│
├── comercial/
│   ├── custo/
│   │   ├── index.php            # Listagem + formulário de cálculo
│   │   └── api.php
│   ├── formacao_preco/
│   │   ├── index.php            # Listagem + formulário de cálculo
│   │   └── api.php
│   └── orcamentos/
│       ├── index.php            # Listagem de orçamentos
│       ├── form.php             # Formulário de criação/edição
│       ├── visualizar.php       # Visualização formatada
│       ├── pdf.php              # Geração de PDF
│       └── api.php
│
├── relatorios/
│   ├── estoque/
│   │   ├── index.php
│   │   └── export.php           # Gera PDF/XLS
│   ├── movimentacao/
│   │   ├── index.php
│   │   └── export.php
│   └── tabela_precos/
│       ├── index.php
│       └── export.php
│
├── configuracoes/
│   ├── empresa/
│   │   ├── index.php
│   │   └── api.php
│   └── perfil/
│       ├── index.php
│       └── api.php
│
└── docs/
    ├── INSTALACAO.md
    ├── ARQUITETURA.md
    ├── API.md
    └── CALCULOS.md              # Documentação das fórmulas
```

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `categorias`
```sql
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ncm VARCHAR(10) NOT NULL COMMENT 'Formato: 9999.99.99',
    perc_seguro DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    perc_ii DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_pis DECIMAL(5,2) NOT NULL DEFAULT 2.10,
    perc_cofins DECIMAL(5,2) NOT NULL DEFAULT 9.65,
    perc_ipi DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_antidumping DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    perc_icms DECIMAL(5,2) NOT NULL DEFAULT 19.50,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `unidades`
```sql
CREATE TABLE unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(20) NOT NULL,
    sigla VARCHAR(5) NOT NULL UNIQUE,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `clientes`
```sql
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj_cpf VARCHAR(18) NULL COMMENT 'Máscara: 99.999.999/9999-99 ou 999.999.999-99',
    cep VARCHAR(10) NULL,
    endereco VARCHAR(100) NULL,
    numero VARCHAR(10) NULL,
    complemento VARCHAR(50) NULL,
    bairro VARCHAR(60) NULL,
    cidade VARCHAR(70) NULL,
    uf CHAR(2) NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(50) NULL,
    contato VARCHAR(50) NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `fornecedores`
```sql
CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(50) NULL,
    contato VARCHAR(50) NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `produtos`
```sql
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    unidade_sigla VARCHAR(5) NOT NULL,
    fornecedor_id INT NULL,
    referencia VARCHAR(50) NULL,
    descricao TEXT NULL,
    especificacoes TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria_id),
    INDEX idx_fornecedor (fornecedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `tabela_precos`
```sql
CREATE TABLE tabela_precos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    multiplicador DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Ex: 1.00 = 100%, 0.90 = 90%',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `custo_produtos`
```sql
CREATE TABLE custo_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    valor_prod_usd DECIMAL(15,2) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    cotacao_dolar DECIMAL(10,4) NOT NULL,
    valor_prod_brl DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_usd * quantidade * cotacao_dolar) STORED,
    
    frete_usd DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    frete_brl DECIMAL(15,2) GENERATED ALWAYS AS (frete_usd * cotacao_dolar) STORED,
    
    perc_seguro DECIMAL(5,2) NOT NULL,
    valor_seguro DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_seguro / 100) STORED,
    
    perc_ii DECIMAL(5,2) NOT NULL,
    valor_ii DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl) / (1 - perc_ii / 100) * perc_ii / 100) STORED,
    
    perc_pis DECIMAL(5,2) NOT NULL,
    valor_pis DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_pis / 100) * perc_pis / 100) STORED,
    
    perc_cofins DECIMAL(5,2) NOT NULL,
    valor_cofins DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_cofins / 100) * perc_cofins / 100) STORED,
    
    perc_ipi DECIMAL(5,2) NOT NULL,
    valor_ipi DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_ipi / 100) * perc_ipi / 100) STORED,
    
    perc_desp_aduaneiras DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    valor_comissao_compra DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    valor_desp_aduaneiras DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_comissao_compra) * perc_desp_aduaneiras / 100) STORED,
    
    perc_antidumping DECIMAL(5,2) NOT NULL,
    valor_antidumping DECIMAL(15,2) GENERATED ALWAYS AS ((valor_prod_brl + frete_brl + valor_seguro) * perc_antidumping / 100) STORED,
    
    base_icms DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_antidumping) STORED,
    
    perc_icms DECIMAL(5,2) NOT NULL,
    valor_icms DECIMAL(15,2) GENERATED ALWAYS AS (base_icms / (1 - perc_icms / 100) * perc_icms / 100) STORED,
    
    perc_custo_financeiro DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    valor_custo_financeiro DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_custo_financeiro / 100) STORED,
    
    perc_iof DECIMAL(5,2) NOT NULL DEFAULT 0.38,
    valor_iof DECIMAL(15,2) GENERATED ALWAYS AS (valor_prod_brl * perc_iof / 100) STORED,
    
    frete_regional DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    custo_total DECIMAL(15,2) GENERATED ALWAYS AS (base_icms + valor_icms + frete_regional + valor_comissao_compra + valor_custo_financeiro + valor_iof + valor_desp_aduaneiras) STORED,
    valor_unitario DECIMAL(15,2) GENERATED ALWAYS AS (custo_total / quantidade) STORED,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `formacao_precos`
```sql
CREATE TABLE formacao_precos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    custo_unitario DECIMAL(15,2) NOT NULL COMMENT 'Vem de custo_produtos.valor_unitario',
    valor_venda DECIMAL(15,2) NOT NULL,
    
    perc_material DECIMAL(5,2) NOT NULL DEFAULT 70.00,
    valor_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_material / 100) STORED,
    perc_servico DECIMAL(5,2) GENERATED ALWAYS AS (100 - perc_material) STORED,
    valor_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda - valor_material) STORED,
    
    perc_desp_admin DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    valor_desp_admin DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_desp_admin / 100) STORED,
    
    perc_desp_fixas DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    valor_desp_fixas DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_desp_fixas / 100) STORED,
    
    perc_comissao_venda DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    valor_comissao_venda DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_comissao_venda / 100) STORED,
    
    perc_pos_venda DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    valor_pos_venda DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda * perc_pos_venda / 100) STORED,
    
    perc_icms_venda DECIMAL(5,2) NOT NULL DEFAULT 19.50,
    icms_custo_unitario DECIMAL(15,2) NOT NULL COMMENT 'Vem de custo_produtos (valor_icms / quantidade)',
    valor_icms_venda DECIMAL(15,2) GENERATED ALWAYS AS ((valor_venda * perc_icms_venda / 100) - icms_custo_unitario) STORED,
    
    valor_montagem DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    perc_imp_interno_material DECIMAL(5,2) NOT NULL DEFAULT 3.65,
    valor_imp_interno_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_material * perc_imp_interno_material / 100) STORED,
    
    perc_imp_interno_servico DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    valor_imp_interno_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_servico * perc_imp_interno_servico / 100) STORED,
    
    total_desp_venda DECIMAL(15,2) GENERATED ALWAYS AS (valor_desp_admin + valor_desp_fixas + valor_comissao_venda + valor_pos_venda + valor_icms_venda + valor_imp_interno_material + valor_imp_interno_servico + valor_montagem) STORED,
    
    margem_liquida DECIMAL(15,2) GENERATED ALWAYS AS (valor_venda - custo_unitario - total_desp_venda) STORED,
    perc_margem_liquida DECIMAL(5,2) GENERATED ALWAYS AS ((margem_liquida / valor_venda) * 100) STORED,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `orcamentos`
```sql
CREATE TABLE orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL COMMENT 'Auto-gerado: ORC-YYYYMMDD-001',
    cliente_id INT NOT NULL,
    tabela_preco_id INT NOT NULL,
    data_criacao DATE NOT NULL,
    validade DATE NOT NULL,
    status ENUM('Rascunho', 'Enviado', 'Aprovado', 'Rejeitado', 'Cancelado') DEFAULT 'Rascunho',
    observacoes TEXT NULL,
    
    subtotal_material DECIMAL(15,2) DEFAULT 0.00,
    subtotal_servico DECIMAL(15,2) DEFAULT 0.00,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    
    total_ipi DECIMAL(15,2) DEFAULT 0.00,
    
    tipo_desconto ENUM('valor', 'percentual') DEFAULT 'percentual',
    desconto_valor DECIMAL(15,2) DEFAULT 0.00,
    desconto_percentual DECIMAL(5,2) DEFAULT 0.00,
    
    total_geral DECIMAL(15,2) DEFAULT 0.00,
    
    prazo_entrega VARCHAR(100) NULL,
    condicao_pagamento TEXT NULL,
    condicao_entrega TEXT NULL,
    condicoes_gerais TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (tabela_preco_id) REFERENCES tabela_precos(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_data_criacao (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `orcamento_itens`
```sql
CREATE TABLE orcamento_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orcamento_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    valor_unitario DECIMAL(15,2) NOT NULL COMMENT 'Editável, vem de formacao_precos * multiplicador tabela',
    valor_total DECIMAL(15,2) GENERATED ALWAYS AS (quantidade * valor_unitario) STORED,
    
    perc_material DECIMAL(5,2) NOT NULL COMMENT 'Vem de formacao_precos',
    valor_material DECIMAL(15,2) GENERATED ALWAYS AS (valor_total * perc_material / 100) STORED,
    
    perc_servico DECIMAL(5,2) GENERATED ALWAYS AS (100 - perc_material) STORED,
    valor_servico DECIMAL(15,2) GENERATED ALWAYS AS (valor_total - valor_material) STORED,
    
    perc_margem_liquida DECIMAL(5,2) NOT NULL COMMENT 'Recalculado com base no valor_unitario editado',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `estoque`
```sql
CREATE TABLE estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL UNIQUE,
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `movimentacao_estoque`
```sql
CREATE TABLE movimentacao_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    data_movimentacao DATE NOT NULL,
    referencia VARCHAR(100) NULL COMMENT 'NF, Orçamento, etc',
    observacao TEXT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_produto (produto_id),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `usuarios`
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL COMMENT 'password_hash()',
    tipo ENUM('administrador', 'colaborador') NOT NULL DEFAULT 'administrador',
    tema ENUM('claro', 'escuro') DEFAULT 'claro',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador');
```

#### Tipos de Usuário

| Tipo | Acesso |
|------|--------|
| `administrador` | Acesso total ao sistema |
| `colaborador` | Sem acesso a: Custo de Produto, Formação de Preço, Cadastro de Usuários |

- O sidebar oculta automaticamente os itens restritos para Colaboradores.
- As páginas e APIs restritas usam `requireAdmin()` — retorna 403 em APIs e redireciona para o dashboard em páginas.
- O tipo do usuário é armazenado na sessão (`$_SESSION['user']['tipo']`) e verificado via `isAdmin()` / `requireAdmin()` em `includes/auth.php`.

### Tabela: `empresa`
```sql
CREATE TABLE empresa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18) NULL,
    telefone VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    cep VARCHAR(10) NULL,
    endereco VARCHAR(100) NULL,
    numero VARCHAR(10) NULL,
    complemento VARCHAR(50) NULL,
    bairro VARCHAR(60) NULL,
    cidade VARCHAR(70) NULL,
    uf CHAR(2) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro padrão
INSERT INTO empresa (nome) VALUES ('Ksabox - Arquitetura Modular');
```

---

## ⚙️ Arquivos de Configuração

### `.env`
```ini
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=ksabox_controle
APP_SECRET=ksabox_secret_key_2024_php8
APP_URL=http://localhost/ksabox-controle
```

### `.gitignore`
```
.env
*.log
tmp/
uploads/
.vscode/
.idea/
```

---

## 🔐 Padrões de Código e Segurança

### Autenticação
```php
// Toda página protegida inicia com:
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Páginas exclusivas de Administrador usam:
requireAdmin(); // redireciona Colaboradores com flash_error

// Verificação pontual de tipo:
if (isAdmin()) { /* só Administrador chega aqui */ }
```

### Acesso ao Banco
```php
// Sempre usar prepared statements via db()
$stmt = db()->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Saída HTML Segura
```php
// Escapar SEMPRE antes de imprimir
echo h($cliente['nome']);
echo moneyBr($produto['preco']);
echo dateBr($orcamento['data_criacao']);
```

### Formatação de Dinheiro
```javascript
// JavaScript: Formatação automática em inputs
function formatMoney(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) / 100).toFixed(2);
    input.value = 'R$ ' + value.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
```

### APIs JSON
```php
// Estrutura padrão de api.php
header('Content-Type: application/json');
$input = getInput(); // Suporta POST form e JSON body

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lógica de criação/atualização
    jsonResponse(['success' => true, 'data' => $result]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Lógica de exclusão
    jsonResponse(['success' => true]);
}
```

---

## 📊 Fórmulas de Cálculo (Detalhadas)

### Custo de Produto
```
1. valor_prod_brl = valor_prod_usd × quantidade × cotacao_dolar
2. frete_brl = frete_usd × cotacao_dolar
3. valor_seguro = valor_prod_brl × (perc_seguro / 100)
4. valor_ii = (valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_ii/100) × (perc_ii / 100)
5. valor_pis = (valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_pis/100) × (perc_pis / 100)
6. valor_cofins = (valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_cofins/100) × (perc_cofins / 100)
7. valor_ipi = (valor_prod_brl + frete_brl + valor_seguro) / (1 - perc_ipi/100) × (perc_ipi / 100)
8. valor_desp_aduaneiras = (valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_comissao_compra) × (perc_desp_aduaneiras / 100)
9. valor_antidumping = (valor_prod_brl + frete_brl + valor_seguro) × (perc_antidumping / 100)
10. base_icms = valor_prod_brl + frete_brl + valor_seguro + valor_ii + valor_pis + valor_cofins + valor_ipi + valor_antidumping
11. valor_icms = base_icms / (1 - perc_icms/100) × (perc_icms / 100)
12. valor_custo_financeiro = valor_prod_brl × (perc_custo_financeiro / 100)
13. valor_iof = valor_prod_brl × (perc_iof / 100)
14. valor_comissao_compra = valor_prod_brl × (perc_comissao_compra / 100)
15. custo_total = base_icms + valor_icms + frete_regional + valor_comissao_compra + valor_custo_financeiro + valor_iof + valor_desp_aduaneiras
16. valor_unitario = custo_total / quantidade
```

### Formação de Preço
```
1. custo_unitario = [vem de custo_produtos.valor_unitario]
2. valor_venda = custo_unitario × 2 [inicial, editável]
3. valor_material = valor_venda × (perc_material / 100)
4. perc_servico = 100 - perc_material
5. valor_servico = valor_venda - valor_material
6. valor_desp_admin = valor_venda × (perc_desp_admin / 100)
7. valor_desp_fixas = valor_venda × (perc_desp_fixas / 100)
8. valor_comissao_venda = valor_venda × (perc_comissao_venda / 100)
9. valor_pos_venda = valor_venda × (perc_pos_venda / 100)
10. icms_custo_unitario = custo_produtos.valor_icms / custo_produtos.quantidade
11. valor_icms_venda = (valor_venda × perc_icms_venda / 100) - icms_custo_unitario
12. valor_imp_interno_material = valor_material × (perc_imp_interno_material / 100)
13. valor_imp_interno_servico = valor_servico × (perc_imp_interno_servico / 100)
14. total_desp_venda = valor_desp_admin + valor_desp_fixas + valor_comissao_venda + valor_pos_venda + valor_icms_venda + valor_imp_interno_material + valor_imp_interno_servico + valor_montagem
15. margem_liquida = valor_venda - custo_unitario - total_desp_venda
16. perc_margem_liquida = (margem_liquida / valor_venda) × 100
```

### Orçamento - Item
```
1. valor_total = quantidade × valor_unitario
2. valor_material = valor_total × (perc_material / 100)
3. valor_servico = valor_total - valor_material
4. Recalcular perc_margem_liquida quando valor_unitario for editado
```

### Orçamento - Totalizadores
```
1. subtotal_material = SUM(orcamento_itens.valor_material)
2. subtotal_servico = SUM(orcamento_itens.valor_servico)
3. subtotal = subtotal_material + subtotal_servico
4. total_ipi = [calcular IPI quando categoria do produto tiver perc_ipi > 0]
5. desconto_aplicado = tipo_desconto == 'valor' ? desconto_valor : (subtotal × desconto_percentual / 100)
6. total_geral = subtotal + total_ipi - desconto_aplicado
```

---

## 🎨 UI/UX - Requisitos

### Layout Responsivo
- **Sidebar colapsável** em mobile
- **Tabelas responsivas** com scroll horizontal
- **Cards** para exibição de métricas no dashboard
- **Modais** para formulários de criação rápida

### Componentes Padrão
1. **Toast notifications** (sucesso, erro, info)
2. **Modal de confirmação** para exclusões
3. **Loading spinners** durante requisições
4. **Badges** para status (Ativo/Inativo, Status de orçamento)
5. **Inputs formatados** (dinheiro, percentual, data, CPF/CNPJ, CEP)

### Tema Claro/Escuro
- Variável CSS: `--bg-primary`, `--text-primary`, etc.
- Classe `dark` no `<html>` toggle
- Persistência em `usuarios.tema`

---

## 📈 Dashboard - Métricas

```javascript
// Dados a exibir
{
    contadores: {
        clientes_ativos: int,
        produtos_ativos: int,
        total_categorias: int,
        total_orcamentos: int,
        total_aprovado: float // SUM(total_geral) WHERE status='Aprovado'
    },
    grafico_linha: {
        labels: ['Jan', 'Fev', 'Mar', ...], // Últimos 6 meses
        data: [total_jan, total_fev, ...] // SUM(total_geral) por mês
    },
    grafico_rosca: {
        labels: ['Rascunho', 'Enviado', 'Aprovado', 'Rejeitado', 'Cancelado'],
        data: [count_rascunho, count_enviado, ...]
    },
    ultimos_orcamentos: [
        {numero, cliente_nome, data_criacao, total_geral, status}
    ] // LIMIT 10
}
```

---

## 🧪 Checklist de Implementação

### Fase 1: Estrutura Base
- [ ] Criar estrutura de diretórios
- [ ] Configurar `.env` e `config.php`
- [ ] Implementar `db.php` (singleton PDO)
- [ ] Implementar `auth.php` (login/logout/sessão)
- [ ] Implementar `functions.php` (h, moneyBr, dateBr, jsonResponse, getInput)
- [ ] Criar `setup.php` (criar banco + tabelas + dados iniciais)
- [ ] Criar layout (`header.php`, `sidebar.php`, `footer.php`)
- [ ] Implementar `login.php` e `logout.php`
- [ ] Implementar `dashboard.php` com métricas vazias

### Fase 2: Cadastros Básicos (CRUDs)
- [ ] Unidades (index.php + api.php)
- [ ] Categorias (index.php + api.php)
- [ ] Fornecedores (index.php + api.php)
- [ ] Clientes (index.php + api.php com máscaras CEP/CNPJ/CPF)
- [ ] Tabelas de Preços (index.php + api.php)
- [ ] Usuários (index.php + api.php com hash de senha)

### Fase 3: Cadastro de Produtos
- [ ] Produtos (index.php + api.php)
- [ ] Relacionar com categorias/fornecedores/unidades
- [ ] Validações de campos obrigatórios

### Fase 4: Módulo de Custo
- [ ] Criar `comercial/custo/index.php`
  - [ ] Listar produtos SEM custo (LEFT JOIN com `custo_produtos`)
  - [ ] Listar produtos COM custo
  - [ ] Modal/formulário de cálculo de custo
- [ ] Criar `comercial/custo/api.php`
  - [ ] Endpoint de criação de custo
  - [ ] Endpoint de atualização de custo
  - [ ] Validação de fórmulas via GENERATED columns
- [ ] JavaScript para formatação de inputs (dinheiro, percentual)
- [ ] JavaScript para cálculo em tempo real (preview)

### Fase 5: Módulo de Formação de Preço
- [ ] Criar `comercial/formacao_preco/index.php`
  - [ ] Listar produtos SEM formação de preço
  - [ ] Listar produtos COM formação de preço
  - [ ] Modal/formulário de cálculo de preço
- [ ] Criar `comercial/formacao_preco/api.php`
  - [ ] Endpoint de criação de preço
  - [ ] Endpoint de atualização de preço
- [ ] JavaScript para cálculo dinâmico de margem

### Fase 6: Módulo de Orçamentos
- [ ] Criar `comercial/orcamentos/index.php` (listagem + filtros)
- [ ] Criar `comercial/orcamentos/form.php` (criação/edição)
  - [ ] Seletor de cliente (com botão de cadastro rápido)
  - [ ] Seletor de tabela de preço
  - [ ] Adição de itens (produto + quantidade + valor editável)
  - [ ] Cálculo de totais (material, serviço, IPI, desconto)
  - [ ] Campos: prazo_entrega, condicoes_*
- [ ] Criar `comercial/orcamentos/visualizar.php` (prévia formatada)
- [ ] Criar `comercial/orcamentos/pdf.php` (geração de PDF com TCPDF ou DomPDF)
- [ ] Criar `comercial/orcamentos/api.php`
  - [ ] CRUD de orçamentos
  - [ ] CRUD de itens
  - [ ] Recalcular totais ao adicionar/remover itens

### Fase 7: Controle de Estoque
- [ ] Criar `estoque/entrada/index.php` + `api.php`
- [ ] Criar `estoque/saida/index.php` + `api.php`
- [ ] Trigger para atualizar tabela `estoque` após movimentação
- [ ] Validação: impedir saída maior que saldo

### Fase 8: Relatórios
- [ ] `relatorios/estoque/index.php` (saldo atual por produto)
- [ ] `relatorios/movimentacao/index.php` (filtro por período)
- [ ] `relatorios/tabela_precos/index.php` (preços finais)
- [ ] Implementar exportação PDF (TCPDF)
- [ ] Implementar exportação XLS (PhpSpreadsheet)

### Fase 9: Configurações
- [ ] `configuracoes/empresa/index.php` (editar dados da empresa)
- [ ] `configuracoes/perfil/index.php` (alterar tema, nome, email, senha)

### Fase 10: Dashboard Final
- [ ] Implementar queries de métricas
- [ ] Integrar Chart.js (gráfico de linha + rosca)
- [ ] Exibir contadores de clientes/produtos/orçamentos
- [ ] Tabela de últimos 10 orçamentos

### Fase 11: Polimentos
- [ ] Adicionar máscaras (CEP, CNPJ/CPF, telefone, NCM)
- [ ] Validações client-side (required, min, max)
- [ ] Toasts de confirmação/erro
- [ ] Loading states em botões
- [ ] Tema dark mode funcional
- [ ] Responsividade mobile

### Fase 12: Documentação
- [ ] `docs/INSTALACAO.md`
- [ ] `docs/ARQUITETURA.md`
- [ ] `docs/API.md` (endpoints e payloads)
- [ ] `docs/CALCULOS.md` (fórmulas detalhadas)

---

## 🚀 Instruções de Instalação

1. **Clonar/criar projeto** na pasta `C:/wamp64/www/ksabox-controle`
2. **Configurar `.env`** com credenciais do MySQL
3. **Acessar** `http://localhost/ksabox-controle/setup.php` no navegador
4. **Aguardar criação** de banco, tabelas e dados iniciais
5. **Deletar** o arquivo `setup.php` após conclusão
6. **Fazer login** com `admin@admin.com` / `admin123`

---

## 📦 Dependências Externas (CDNs)

```html
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<!-- Opcional: Font Awesome para ícones -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

---

## 🔧 Funções Utilitárias (functions.php)

```php
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function moneyBr($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function dateBr($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    return $_POST;
}

function isDark() {
    return ($_SESSION['user']['tema'] ?? 'claro') === 'escuro';
}
```

---

## 🎯 Observações Finais

- **Sempre use prepared statements** para prevenir SQL injection
- **Escape HTML** com `h()` antes de imprimir qualquer dado
- **Valide inputs** tanto no cliente (JS) quanto no servidor (PHP)
- **Use GENERATED columns** para cálculos automáticos no banco
- **Mantenha APIs RESTful**: GET para leitura, POST para escrita, DELETE para exclusão
- **Documente mudanças** na pasta `docs/`
- **Teste cada módulo** antes de avançar para o próximo

---

**FIM DA ESPECIFICAÇÃO**
