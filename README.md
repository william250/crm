# Sistema CRM - Configuração Local

Este é um sistema CRM completo desenvolvido em PHP com MySQL, incluindo gestão de leads, clientes, agendamentos, contratos, cobranças e pagamentos.

## 📋 Pré-requisitos

Antes de iniciar, certifique-se de ter instalado:

- **PHP 7.4+** com extensões:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Composer** (opcional, para dependências futuras)

### Verificar instalação do PHP
```bash
php --version
php -m | grep -i pdo
```

### Verificar instalação do MySQL
```bash
mysql --version
```

## 🚀 Configuração Rápida

### 1. Clone/Download do Projeto
Se ainda não fez, baixe os arquivos do projeto para a pasta `d:\Dev\crm`

### 2. Configurar Variáveis de Ambiente
O arquivo `.env` já foi criado com as configurações padrão:

```env
DB_HOST=localhost
DB_NAME=crm_system
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

**Edite o arquivo `.env`** se suas configurações do MySQL forem diferentes:
- `DB_HOST`: Endereço do servidor MySQL (padrão: localhost)
- `DB_USER`: Usuário do MySQL (padrão: root)
- `DB_PASS`: Senha do MySQL (padrão: vazio)

### 3. Criar e Configurar o Banco de Dados
Execute o script de configuração automática:

```bash
php setup_database.php
```

Este script irá:
- ✅ Conectar ao MySQL
- ✅ Criar o banco de dados `crm_system`
- ✅ Executar todas as migrações
- ✅ Inserir dados iniciais de exemplo
- ✅ Verificar se tudo foi criado corretamente

### 4. Iniciar o Servidor Web
```bash
php -S localhost:8000 -t public
```

### 5. Acessar o Sistema
Abra seu navegador e acesse: **http://localhost:8000**

## 👥 Usuários Padrão

O sistema vem com 3 usuários pré-configurados:

| Email | Senha | Perfil |
|-------|-------|--------|
| admin@sistema.com | password | Administrador |
| vendedor@sistema.com | password | Vendedor |
| financeiro@sistema.com | password | Financeiro |

## 📊 Estrutura do Banco de Dados

O sistema possui as seguintes tabelas principais:

- **users** - Usuários do sistema
- **leads** - Leads de vendas
- **clients** - Clientes convertidos
- **appointments** - Agendamentos
- **contracts** - Contratos
- **signatures** - Assinaturas digitais
- **charges** - Cobranças/Faturas
- **payments** - Pagamentos
- **interactions** - Histórico de interações

## 🔧 Solução de Problemas

### Erro de Conexão com MySQL
```
Connection error: SQLSTATE[HY000] [1045] Access denied
```
**Solução**: Verifique as credenciais no arquivo `.env`

### Erro "Database does not exist"
```
SQLSTATE[42000]: Syntax error or access violation: 1049 Unknown database
```
**Solução**: Execute novamente `php setup_database.php`

### Erro de Permissões
```
Access denied for user 'root'@'localhost'
```
**Solução**: 
1. Verifique se o MySQL está rodando
2. Confirme usuário e senha no MySQL
3. Garanta que o usuário tem permissões para criar bancos

### Porta 8000 já em uso
```
Address already in use
```
**Solução**: Use outra porta:
```bash
php -S localhost:8080 -t public
```

## 📁 Estrutura de Arquivos

```
crm/
├── .env                     # Configurações de ambiente
├── setup_database.php       # Script de configuração do BD
├── README.md               # Este arquivo
├── app/
│   ├── controllers/        # Controladores da aplicação
│   ├── models/            # Modelos de dados
│   ├── middleware/        # Middlewares
│   └── routes/           # Rotas da API
├── config/
│   ├── database.php      # Configuração do banco
│   └── routes.php        # Configuração de rotas
├── database/
│   ├── migrations/       # Scripts de migração
│   └── schema.sql       # Schema completo
└── public/              # Arquivos públicos (HTML, CSS, JS)
    ├── index.php        # Ponto de entrada
    ├── assets/         # CSS e JavaScript
    └── *.html         # Páginas do sistema
```

## 🔄 Comandos Úteis

### Resetar o Banco de Dados
```bash
# Apagar e recriar tudo
php setup_database.php
```

### Verificar Status do Servidor
```bash
# Ver se o servidor está rodando
netstat -an | findstr :8000
```

### Backup do Banco
```bash
mysqldump -u root -p crm_system > backup_crm.sql
```

### Restaurar Backup
```bash
mysql -u root -p crm_system < backup_crm.sql
```

## 📝 Próximos Passos

Após a configuração inicial, você pode:

1. **Personalizar usuários**: Alterar senhas padrão
2. **Configurar email**: Definir SMTP no `.env`
3. **Adicionar dados**: Cadastrar seus próprios leads e clientes
4. **Customizar**: Modificar layouts e funcionalidades

## 🆘 Suporte

Se encontrar problemas:

1. Verifique os logs de erro do PHP
2. Confirme se todas as extensões PHP estão instaladas
3. Teste a conexão com o MySQL separadamente
4. Verifique as permissões de arquivo

---

**Sistema CRM** - Desenvolvido para gestão completa de relacionamento com clientes.