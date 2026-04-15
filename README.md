# Sistema de Requisição de Compras — Binário Tecnologia

Sistema web interno desenvolvido pelo setor de T.I da Binário Tecnologia para digitalizar e organizar o processo de requisição de compras entre os setores da empresa.

---

## Sobre o Projeto

Antes deste sistema, as solicitações de compra eram feitas de forma manual (WhatsApp, papel, verbal). Com ele, qualquer vendedor ou colaborador pode abrir uma requisição pelo celular ou computador, e o setor de compras recebe um e-mail automático com todos os detalhes. Quando a compra é aprovada, o pessoal da entrada também é notificado automaticamente.

---

## Funcionalidades

- Login e cadastro de usuários com autenticação segura
- Criação de requisições com múltiplos produtos por pedido
- Campos por requisição: vendedor, fornecedor, urgência, motivo, observação (filial) e lista de produtos
- E-mail automático para o setor de compras ao criar uma requisição
- E-mail automático para o setor de entrada ao aprovar uma compra
- Painel administrativo para aprovar, rejeitar e acompanhar todas as requisições
- Histórico de requisições por usuário com filtros (vendedor, produto, data)
- Paginação automática na listagem
- Design responsivo — funciona em celular e computador

---

## Tecnologias Utilizadas

| Tecnologia | Uso |
|---|---|
| Laravel 12 | Framework PHP backend |
| PHP 8.2 | Linguagem principal |
| SQLite | Banco de dados (desenvolvimento) |
| Laravel Breeze | Autenticação (login, registro, perfil) |
| Blade | Templates HTML |
| Tailwind CSS | Estilização base |
| Alpine.js | Interatividade (menu mobile) |
| Vite | Compilação de assets |
| Laravel Mail + SMTP Gmail | Envio de e-mails automáticos |

---

## Fluxo de E-mails

```
Vendedor cria requisição
        ↓
  compras@binariotecnologia.com.br recebe e-mail com todos os itens
        ↓
  Admin aprova pelo painel
        ↓
  fiscal@binariotecnologia.com.br
  fiscal.2@binariotecnologia.com.br  ← recebem aviso para aguardar entrega
```

---

## Como Rodar Localmente

### Pré-requisitos
- PHP 8.2+
- Composer
- Node.js + NPM

### Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/Guii15/Requisicao-compras.git
cd Requisicao-compras

# 2. Instale as dependências PHP
composer install

# 3. Instale as dependências JS
npm install

# 4. Copie o arquivo de ambiente
cp .env.example .env

# 5. Gere a chave da aplicação
php artisan key:generate

# 6. Crie o banco de dados e rode as migrations
php artisan migrate

# 7. Compile os assets
npm run build

# 8. Inicie o servidor
php artisan serve
```

Acesse em: `http://localhost:8000`

---

## Configuração de E-mail

No arquivo `.env`, configure as variáveis de e-mail:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD="senha-de-app-do-gmail"
MAIL_FROM_ADDRESS=seu-email@gmail.com
MAIL_FROM_NAME="Requisição de Compras - Binário"

# E-mails de destino
ENTRADA_EMAIL=fiscal@binariotecnologia.com.br
ENTRADA_EMAIL_2=fiscal.2@binariotecnologia.com.br
```

> **Atenção:** Use uma **Senha de App** do Gmail (não a senha normal). Ative a verificação em duas etapas na conta Google e gere a senha em: Conta Google → Segurança → Senhas de app.

---

## Como Criar um Usuário Administrador

1. O usuário deve se cadastrar normalmente no sistema
2. Execute no terminal dentro da pasta do projeto:

```bash
php artisan tinker --execute="User::where('email', 'email@exemplo.com')->first()->update(['is_admin' => true]);"
```

Administradores têm acesso ao painel Admin onde podem:
- Ver todas as requisições de todos os usuários
- Filtrar por status, vendedor e data
- Aprovar, rejeitar ou manter como pendente
- Adicionar observações em cada requisição

---

## Estrutura Principal

```
app/
├── Http/Controllers/
│   ├── PurchaseRequestController.php   # Criação e listagem de requisições
│   └── AdminController.php             # Painel administrativo
├── Mail/
│   ├── PurchaseRequestCreated.php      # E-mail para compras
│   └── PurchaseRequestApproved.php     # E-mail para entrada
├── Models/
│   ├── PurchaseRequest.php
│   └── User.php
resources/views/
├── requests/
│   ├── create.blade.php                # Formulário de nova requisição
│   └── index.blade.php                 # Listagem do usuário
├── admin/
│   └── index.blade.php                 # Painel admin
├── emails/
│   ├── purchase-request-created.blade.php
│   └── purchase-request-approved.blade.php
└── layouts/
    ├── app.blade.php
    └── navigation.blade.php
```

---

## Segurança

- Autenticação obrigatória em todas as rotas
- Middleware exclusivo para rotas administrativas (`AdminMiddleware`)
- Proteção CSRF em todos os formulários
- Validação de dados no backend

---

## Deploy

Para produção, recomendamos:
- **Servidor:** Railway, Render ou VPS (DigitalOcean / Hostinger)
- **Banco de dados:** Trocar SQLite por MySQL
- **Variáveis de ambiente:** Configurar no painel do servidor

---

## Desenvolvido por

**Setor de T.I — Binário Tecnologia**  
Sistema desenvolvido internamente para uso exclusivo da empresa.
