# Prompt para Claude Code - Sistema Ksabox

## 🎯 PROMPT PRINCIPAL (Copie e cole no Claude Code)

```
Você é um desenvolvedor PHP sênior especializado em sistemas de gestão empresarial.

Sua missão é desenvolver COMPLETAMENTE o "Sistema de Orçamento e Controle de Estoque Ksabox" seguindo RIGOROSAMENTE a especificação técnica fornecida no arquivo PROJETO_ESPECIFICACAO.md.


INSTRUÇÕES CRÍTICAS:

1. LEIA PRIMEIRO o arquivo PROJETO_ESPECIFICACAO.md na íntegra antes de começar qualquer código
2. Siga a estrutura de diretórios EXATAMENTE como especificada
3. Implemente TODAS as tabelas do banco com os campos e tipos EXATOS descritos
4. Use APENAS as tecnologias especificadas: PHP 8+, MySQL (PDO), Tailwind CSS (CDN), Chart.js, JavaScript puro
5. Siga os padrões de código obrigatórios: prepared statements, h() para escape HTML, moneyBr() para dinheiro
6. Implemente TODAS as fórmulas de cálculo exatamente como documentadas
7. Use GENERATED COLUMNS do MySQL para cálculos automáticos em custo_produtos e formacao_precos
8. Crie TODOS os módulos listados no checklist de implementação
9. Garanta que o sistema funcione em WampServer (localhost/ksabox-controle)
10. Ao final, crie a documentação completa na pasta docs/
11. Desenvolva por etapas. Sempre pergunte se de iniciar uma fase. Nunca inicie uma fase sem a confirmação.

ETAPAS DE DESENVOLVIMENTO (siga esta ordem):

FASE 1: Estrutura Base e Configuração
- Criar toda estrutura de pastas
- Implementar includes/ (config.php, db.php, auth.php, functions.php)
- Criar layout/ (header.php, sidebar.php, footer.php)
- Implementar setup.php com criação de banco completo
- Criar login.php e logout.php
- Criar dashboard.php (estrutura inicial)

FASE 2: Cadastros Básicos (CRUDs completos)
- Unidades
- Categorias
- Fornecedores
- Clientes (com máscaras CEP, CNPJ/CPF)
- Tabelas de Preços
- Usuários (com hash de senha)
- Produtos (relacionando tudo)

FASE 3: Módulo Comercial - Custo
- Interface para listar produtos sem custo
- Interface para listar produtos com custo
- Formulário de cálculo de custo com TODAS as fórmulas
- API com validações
- JavaScript para formatação e cálculo em tempo real

FASE 4: Módulo Comercial - Formação de Preço
- Interface para listar produtos sem formação
- Interface para listar produtos com formação
- Formulário de cálculo de preço com TODAS as fórmulas
- API com validações
- Cálculo dinâmico de margem

FASE 5: Módulo Comercial - Orçamentos
- Listagem com filtros por status
- Formulário de criação/edição completo
- Sistema de itens com cálculos
- Visualização formatada
- Geração de PDF
- API completa

FASE 6: Controle de Estoque
- Entrada de estoque
- Saída de estoque
- Movimentações com validações
- Relatórios

FASE 7: Relatórios
- Estoque atual
- Movimentação (com filtro de período)
- Tabela de preços
- Exportação PDF e XLS

FASE 8: Configurações
- Dados da empresa
- Perfil do usuário (tema, senha)

FASE 9: Dashboard Completo
- Implementar TODAS as métricas
- Gráficos Chart.js (linha + rosca)
- Contadores
- Últimos orçamentos

FASE 10: Polimentos e Validações
- Máscaras de input
- Validações client/server
- Toasts e modais
- Tema dark mode
- Responsividade

FASE 11: Documentação Final
- INSTALACAO.md
- ARQUITETURA.md
- API.md
- CALCULOS.md

IMPORTANTE:
- Teste cada módulo antes de avançar
- Garanta que TODAS as fórmulas matemáticas estejam corretas
- Use GENERATED COLUMNS para cálculos automáticos
- Valide dados tanto no frontend quanto backend
- Mantenha o código limpo, comentado e organizado
- Crie sistema COMPLETO e FUNCIONAL

Comece criando a estrutura de diretórios e os arquivos da Fase 1.
```

---

## 📋 CHECKLIST DE EXECUÇÃO NO CLAUDE CODE

### Preparação Inicial
- [ ] Abrir o Claude Code no terminal ou VS Code
- [ ] Ter o WampServer instalado e rodando
- [ ] Navegar até a pasta: `cd C:/wamp64/www/`
- [ ] Criar diretório: `mkdir ksabox-controle && cd ksabox-controle`
- [ ] Ter o arquivo PROJETO_ESPECIFICACAO.md disponível no diretório

### Durante o Desenvolvimento
- [ ] Deixar o Claude Code trabalhar em blocos (uma fase de cada vez)
- [ ] Testar cada módulo após conclusão antes de pedir o próximo
- [ ] Verificar erros no navegador e reportar ao Claude Code
- [ ] Acompanhar a criação de arquivos e diretórios

### Após Conclusão
- [ ] Acessar http://localhost/ksabox-controle/setup.php
- [ ] Aguardar criação do banco de dados
- [ ] Deletar setup.php
- [ ] Fazer login com admin@admin.com / admin123
- [ ] Testar TODOS os módulos
- [ ] Verificar cálculos de custo e formação de preço
- [ ] Testar criação de orçamentos
- [ ] Verificar relatórios e exportações

---

## ⚙️ COMANDOS ÚTEIS NO CLAUDE CODE

### Iniciar o Projeto
```bash
# No terminal
cd C:/wamp64/www/ksabox-controle

# No Claude Code, diga:
"Leia o arquivo PROJETO_ESPECIFICACAO.md e comece pela Fase 1"
```

### Durante o Desenvolvimento
```bash
# Se houver erro, copie a mensagem e diga:
"Estou recebendo este erro: [COLAR ERRO]. Como corrigir?"

# Para revisar um cálculo específico:
"Revise a implementação do cálculo de ICMS no arquivo custo/api.php"

# Para testar uma funcionalidade:
"Como testo o módulo de criação de produtos?"
```

### Correções
```bash
# Se algo não funcionar:
"O cadastro de cliente não está salvando. Verifique o arquivo clientes/api.php"

# Para adicionar validação:
"Adicione validação para impedir que o percentual de material + serviço seja diferente de 100%"
```

---

## 🎨 ORIENTAÇÕES DE INTERAÇÃO COM CLAUDE CODE

### ✅ FAÇA:
1. **Seja específico:** "Crie o módulo de categorias completo com CRUD e máscaras de NCM"
2. **Peça revisões:** "Revise os cálculos da formação de preço para garantir que estão corretos"
3. **Teste progressivamente:** Complete uma fase antes de pedir a próxima
4. **Reporte erros com contexto:** "Erro na linha 45 do arquivo api.php ao salvar categoria"

### ❌ NÃO FAÇA:
1. Não peça tudo de uma vez: "Crie o sistema completo agora"
2. Não pule etapas: testar login antes de criar o banco
3. Não ignore erros: sempre reporte para correção
4. Não modifique manualmente sem avisar: Claude Code pode sobrescrever

---

## 🔍 VALIDAÇÕES IMPORTANTES A FAZER

### Após Fase 1 (Estrutura Base)
```bash
✓ Testar login com admin@admin.com / admin123
✓ Verificar se sidebar aparece corretamente
✓ Testar logout
✓ Verificar se tema claro/escuro funciona
```

### Após Fase 2 (Cadastros)
```bash
✓ Criar uma categoria e verificar se salvou
✓ Criar um cliente com CPF e verificar máscara
✓ Criar um produto relacionado à categoria
✓ Editar e excluir registros
```

### Após Fase 3 (Custo)
```bash
✓ Criar um custo para produto
✓ Verificar se TODOS os cálculos estão corretos
✓ Comparar valores calculados com fórmula manual
✓ Editar custo e verificar recalculo automático
```

### Após Fase 4 (Formação de Preço)
```bash
✓ Criar formação de preço
✓ Verificar margem líquida
✓ Alterar valor de venda e verificar recalculo
✓ Confirmar que percentual material + serviço = 100%
```

### Após Fase 5 (Orçamentos)
```bash
✓ Criar orçamento completo
✓ Adicionar 3-5 produtos
✓ Aplicar desconto
✓ Gerar PDF
✓ Verificar totais (material, serviço, IPI, desconto)
```

---

## 🚨 PROBLEMAS COMUNS E SOLUÇÕES

### Erro de Conexão com Banco
```
Solução: Verificar .env (DB_HOST, DB_USER, DB_PASS, DB_NAME)
Verificar se MySQL do WampServer está rodando
```

### Erro 404 em /ksabox-controle
```
Solução: Verificar se pasta está em C:/wamp64/www/
Verificar configuração do Apache no WampServer
```

### Fórmulas de Cálculo Incorretas
```
Solução: Pedir ao Claude Code: "Revise a fórmula de cálculo de [NOME] 
comparando com a especificação na seção 'Fórmulas de Cálculo'"
```

### Máscaras de Input Não Funcionam
```
Solução: Verificar se JavaScript está carregado no footer.php
Verificar console do navegador (F12) para erros JS
```

### Tema Dark Não Persiste
```
Solução: Verificar se coluna 'tema' existe em 'usuarios'
Verificar se função isDark() está implementada
Verificar se classe 'dark' está sendo aplicada ao <html>
```

---

## 📊 MONITORAMENTO DE PROGRESSO

### Use este checklist durante o desenvolvimento:

```
FASE 1: Estrutura Base          [ ]
FASE 2: Cadastros Básicos       [ ]
FASE 3: Custo                   [ ]
FASE 4: Formação de Preço       [ ]
FASE 5: Orçamentos              [ ]
FASE 6: Estoque                 [ ]
FASE 7: Relatórios              [ ]
FASE 8: Configurações           [ ]
FASE 9: Dashboard               [ ]
FASE 10: Polimentos             [ ]
FASE 11: Documentação           [ ]
```

Marque cada fase conforme completar e testar.

---

## 🎓 DICAS FINAIS

1. **Paciência:** Desenvolvimento completo leva tempo. Não apresse o Claude Code.

2. **Testes Incrementais:** Teste cada funcionalidade antes de avançar. É mais fácil corrigir erros pequenos que grandes.

3. **Backup:** Faça backup do projeto após cada fase importante completada.

4. **Documentação:** Peça ao Claude Code para explicar código complexo quando necessário.

5. **Personalização:** Após sistema completo, você pode pedir ajustes de layout, cores, etc.

6. **Performance:** Se sistema ficar lento, peça otimizações de queries e índices.

7. **Segurança:** Revise validações e sanitizações antes de usar em produção.

---

## 📞 COMANDOS DE EMERGÊNCIA

### Sistema não funciona de jeito nenhum:
```
"Vamos recomeçar do zero. Delete tudo e recomece pela Fase 1, 
mas desta vez crie um arquivo de log detalhado de cada passo."
```

### Cálculos completamente errados:
```
"Ignore o código atual de cálculo. Reimplemente do zero seguindo 
EXATAMENTE as fórmulas da seção 'Fórmulas de Cálculo (Detalhadas)' 
da especificação, passo a passo."
```

### Muitos erros simultâneos:
```
"Pause. Crie um arquivo ERROS.md listando TODOS os problemas atuais.
Depois corrija um por um, começando pelos críticos."
```

---

## ✅ CRITÉRIOS DE ACEITAÇÃO FINAL

O sistema estará completo quando:

- [ ] Login/Logout funcionando
- [ ] TODOS os 7 CRUDs (categorias, produtos, clientes, fornecedores, unidades, tabelas de preço, usuários) funcionando
- [ ] Cálculo de custo gerando valores corretos (comparar com calculadora manual)
- [ ] Formação de preço calculando margem corretamente
- [ ] Orçamentos sendo criados, editados e deletados
- [ ] PDF de orçamento sendo gerado
- [ ] Entrada e saída de estoque atualizando saldos
- [ ] Relatórios sendo exibidos e exportados
- [ ] Dashboard mostrando métricas e gráficos
- [ ] Tema claro/escuro funcionando
- [ ] Máscaras de input aplicadas
- [ ] Sistema responsivo em mobile
- [ ] Documentação completa em docs/

---

**BOA SORTE NO DESENVOLVIMENTO!**

Se tiver dúvidas durante o processo, sempre volte à especificação PROJETO_ESPECIFICACAO.md como fonte da verdade.
