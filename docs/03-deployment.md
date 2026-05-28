# Deployment — Kallme

Como fazer deploy do projeto local para o servidor HostGator.

---

## 🎯 Estratégia: HTTP POST (não FTP direto)

### Por que não FTP?

A HostGator tem um **cache de filesystem** que não reconhece arquivos novos enviados por FTP imediatamente. Resultado: você sobe via FTP, vê o arquivo lá, mas o Apache continua servindo a versão antiga.

### Solução: endpoint PHP no servidor

`install.php` **no servidor** virou um endpoint de deploy:

```php
<?php
require_once __DIR__ . '/config/database.php';
if (!defined('DEPLOY_KEY')) { die('DEPLOY_KEY não configurada'); }
if (($_POST['key'] ?? $_GET['key'] ?? '') !== DEPLOY_KEY) { die('Acesso negado'); }

$base = __DIR__;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $destFile = trim($_POST['path'] ?? '');
    if (empty($destFile)) { die('Path não informado'); }

    $destPath = $base . '/' . $destFile;
    $dir = dirname($destPath);
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }

    if (move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
        chmod($destPath, 0666);
        echo "OK: $destFile";
    } else {
        echo "ERRO: $destFile";
    }
}
```

Como é **executado pelo PHP no servidor**, escreve "por dentro" — o cache não pega.

> ⚠️ Esse `install.php` na raiz **substitui** o instalador original. Não recriar.

---

## 🚀 Como fazer deploy

### Comando único

```bash
bash deploy.sh
```

### O que o script faz

`deploy.sh` itera por uma lista de arquivos e envia cada um via `curl`:

```bash
for file in "${FILES[@]}"; do
    RESULT=$(curl -s -X POST "$DEPLOY_URL" \
        -F "key=$DEPLOY_KEY" \
        -F "path=$file" \
        -F "file=@$file")
    if [[ "$RESULT" == OK* ]]; then
        echo "  ✅ $file"
    else
        echo "  ❌ $file - $RESULT"
    fi
done
```

### Adicionar um novo arquivo ao deploy

Edite `deploy.sh`, adicione o caminho no array `FILES`:

```bash
FILES=(
    ".htaccess"
    "index.php"
    "page-router.php"
    "migrate.php"
    "site.webmanifest"
    "config/database.php"
    "includes/auth.php"
    "includes/functions.php"
    "includes/settings.php"
    "includes/categories.php"
    "includes/site-helpers.php"
    "includes/site-header.php"
    "includes/site-footer.php"
    "includes/site-drawer.php"
    "admin/..."
    "templates/..."
    "assets/css/site.css"
    "assets/css/presell.css"
    "assets/js/header.js"
    "assets/js/presell.js"
    "pages/br/home.php"
    "pages/br/sobre.php"
    # ...
    "novo-arquivo.php"   # ← novo
)
```

### Saída típica

```
🚀 Iniciando deploy para kallme.online...
  ✅ .htaccess
  ✅ index.php
  ✅ page-router.php
  ✅ migrate.php
  ...
✅ Deploy concluído com sucesso! (41 arquivos)
```

> O deploy **sobrescreve mas não deleta**. Arquivos antigos no servidor ficam órfãos (inofensivos se não forem referenciados). Para deletar de verdade, use o Gerenciador de Arquivos do cPanel.

---

## 🗄 Migrações de banco

Schema novo? Edite `migrate.php` adicionando colunas/tabelas. Depois:

```
https://kallme.online/migrate.php?key=<MIGRATE_KEY>
```

`migrate.php` é **idempotente**:
- Adiciona colunas em `pages` apenas se não existirem (`SHOW COLUMNS` antes do `ALTER`)
- Cria índices apenas se ausentes
- Cria tabelas com `CREATE TABLE IF NOT EXISTS` ou recria com detecção de schema (caso de `categories`)
- Faz seed com `INSERT IGNORE` ou `SELECT` prévio

Saída típica:
```
=== MIGRAÇÃO KALLME ===

--- Colunas em `pages` ---
[ SKIP ] language (já existe)
[  OK  ] nova_coluna adicionado
...

--- Tabela `categories` ---
[ SKIP ] `categories` já está no schema novo (preserva edições)

--- Tabela `settings` ---
[  OK  ] Tabela `settings` garantida

--- Seed de settings ---
[ SKIP ] site_name (já existe)
...

✅ Migração concluída!
```

---

## 🔐 Credenciais e URLs

| Recurso | Valor |
|---------|-------|
| FTP host | configurado no cPanel da HostGator |
| FTP porta | 21 |
| FTP user | usuário do cPanel |
| FTP root path | `/home<N>/<usuario>/<dominio>` |
| Deploy URL | `https://<seu-dominio>/install.php` |
| Deploy key | `DEPLOY_KEY` em `config/database.php` (local — gitignored) |
| Migrate URL | `https://<seu-dominio>/migrate.php?key=<MIGRATE_KEY>` |

---

## 🛠 Setup do servidor (do zero, se precisar restaurar)

### 1. cPanel — criar banco MySQL
1. Bancos de dados MySQL → criar banco (ex: `<prefixo>_kallme`)
2. Criar usuário (ex: `<prefixo>_admin`)
3. Associar com **TODOS os privilégios**

### 2. Upload inicial (FTP manual)
1. Subir todos os arquivos para `/home<N>/<usuario>/<dominio>/`
2. Permissões: 0644 em arquivos, 0755 em diretórios

### 3. Configurar `config/database.php`
Copie `config/database.example.php` para `config/database.php` e preencha:
```php
define('DB_NAME', '<seu_banco>');
define('DB_USER', '<seu_usuario>');
define('DB_PASS', '<sua_senha>');
define('DEPLOY_KEY',  '<chave_longa_aleatoria>');
define('MIGRATE_KEY', '<outra_chave_longa>');
```

### 4. Rodar instalador original (cria tabelas + admin padrão)
Use o conteúdo do `install.php` original (do git history ou backup) — cria `users`, `pages` e o admin (`admin`/`admin123`).

### 5. Substituir `install.php` pelo endpoint de deploy
Conteúdo está no topo deste documento.

### 6. Rodar migrate
```
https://kallme.online/migrate.php?key=<MIGRATE_KEY>
```
Cria as colunas extras de `pages`, índices, `categories` (schema bilíngue) e `settings`.

### 7. Acessar admin
```
https://kallme.online/admin/
```
Login: `admin` / `admin123` → **trocar imediatamente** (ver [04-admin-guide.md](04-admin-guide.md)).

---

## 🐛 Troubleshooting

### "Deploy concluído" mas alterações não aparecem
- **Cache do navegador**: Ctrl+Shift+R
- Confira a URL: `kallme.online` (não `www.kallme.online`)
- Se for CSS/JS recém atualizado, faça hard refresh com DevTools aberto

### Arquivo retorna 404
- Confirme no Gerenciador de Arquivos do cPanel se está em `/kallme.online/<caminho>`
- Permissão 0644 ou 0666

### Erro de banco no admin
- Acesse `https://kallme.online/migrate.php?key=<MIGRATE_KEY>` para garantir schema
- Confira `config/database.php` (credenciais e DB_NAME)

### Conexão FTP recusada
- HostGator às vezes bloqueia IPs. Acesse cPanel → Conexões FTP para liberar
- Use o IP direto `162.241.203.225`

### Imagens não aparecem
- Verifique se `uploads/` existe e tem 0755
- Caminho no banco é relativo (`uploads/avatars/xxx.jpg`); `BASE_URL` precisa ser `/`

### Categorias antigas (Costura) ainda aparecem
- Limpe o cache de categorias: salve qualquer página no admin OU delete manualmente `/tmp/kallme_categories_cache.json` no servidor (cPanel → Gerenciador de Arquivos)

---

## 📊 Fluxo completo de uma mudança

```
1. Edite arquivo local em d:/Trabalhos/SistVendas-docs/
2. Salve (Ctrl+S)
3. (opcional) Se mudou schema: edite migrate.php também
4. Terminal: bash deploy.sh
5. (opcional) Se mudou schema: https://kallme.online/migrate.php?key=<MIGRATE_KEY>
6. Navegador: Ctrl+Shift+R em kallme.online/br/
```

---

## ⚠ Itens que NÃO devem ser deletados

| Arquivo no servidor | Motivo |
|---------------------|--------|
| `install.php` | É o endpoint de DEPLOY (não o instalador original) |
| `migrate.php` | Necessário para evoluir o schema |
| `_dbcheck.php` (se existir) | Já neutralizado (responde 410); pode deletar via cPanel se quiser |

---

## 🧹 Arquivos órfãos conhecidos no servidor

O deploy via HTTP POST não deleta. Estes arquivos antigos podem ainda existir fisicamente, mas **não são referenciados**:

- `page.php` (substituído por `page-router.php`)
- `includes/site-sidebar.php` (substituído por `site-drawer.php`)
- `assets/js/sidebar.js` (substituído por `header.js`)

São inofensivos. Para limpar de vez: cPanel → Gerenciador de Arquivos → deletar.
