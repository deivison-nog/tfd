# Sistema TFD (Transporte Fora do Domicílio)

Aplicação web em **PHP + CSS + JavaScript** para gestão operacional e administrativa de TFD.

## Funcionalidades implementadas

- **Autenticação e sessão**
  - Tela de login
  - Usuários demo:
    - Administrativo: `tfdcolares` / `tfd123`
    - Assistente Social: `assistente.social` / `tfd123`
    - Médico: `medico.tfd` / `tfd123`
    - Auxiliar Administrativo: `auxiliar.adm` / `tfd123`
  - Proteção de páginas internas por sessão
- **Dashboard** com indicadores principais
- **Gestão de pacientes**
  - Cadastro, edição, listagem e visualização
  - Campos: nome, CPF, CNS/cartão SUS, telefone, endereço, observações
- **Gestão de processos TFD**
  - Cadastro, edição, listagem e visualização
  - Campos obrigatórios: número do processo, ano de abertura, ano de atualização, local de tratamento
  - Campos adicionais: paciente, CID, especialidade, município destino, data solicitação, prioridade, status, acompanhante, observações
- **Acompanhantes**
  - Cadastro e vínculo com processo TFD
  - Campos: nome, CPF, parentesco, telefone, observações
- **Documentos (opcionais)**
  - Upload real de anexos para paciente, acompanhante e processo
  - Download e exclusão de arquivos enviados pela tela de Documentos
- **Fluxo operacional e status**
  - Status: cadastrado, aguardando análise, pendente de documentos, autorizado, agendado, em viagem, retornado, concluído, negado, cancelado
  - Página com fluxo em **SVG**
- **Viagens**
  - Agendamento e acompanhamento de execução/retorno/conclusão
- **Controle de acesso por perfil**
  - Perfis: Administrativo, Assistente Social, Médico e Auxiliar Administrativo
  - Tela de **Configuração** para marcar/desmarcar acesso a cada item do menu lateral
  - Perfil administrativo com acesso total
- **Parecer profissional**
  - Registro de múltiplos pareceres por processo na tela de visualização
  - Cada usuário pode editar apenas os pareceres que criou
  - Visibilidade controlada por permissão na tela de **Configuração**

## Stack

- PHP 8+
- SQLite (persistência real)
- CSS e JavaScript nativos

## Estrutura do projeto

- `index.php` → front controller na raiz, compatível com XAMPP
- `public/index.php` → front controller alternativo para `php -S -t public`
- `public/assets/` → CSS e JS
- `src/bootstrap.php` → inicialização da aplicação
- `src/config.php` → constantes e status/prioridades
- `src/db.php` → conexão SQLite e bootstrap automático do banco
- `src/auth.php` → autenticação e sessão
- `src/functions.php` → helpers (escape, csrf, flash, redirect)
- `src/pages/` → páginas e regras de cada módulo
- `src/views/` → layout compartilhado (header/footer)
- `database/init.sql` → schema inicial
- `scripts/init_db.php` → recria banco local

## Banco de dados

O sistema usa SQLite em `data/tfd.sqlite`.

Entidades principais:
- `users`
- `patients`
- `tfd_processes`
- `companions`
- `documents`
- `status_history`
- `trips`
- `process_professional_opinions`

## Como executar localmente

### Opção 1: XAMPP 3.3.0

1. Copie a pasta do projeto para o diretório `htdocs` do XAMPP.
   Exemplo:
   ```text
   C:\xampp\htdocs\tfd
   ```
2. Inicie o **Apache** no painel do XAMPP.
3. (Opcional) Recrie o banco:
   ```bash
   php scripts/init_db.php
   ```
4. Acesse no navegador:
   - `http://localhost/tfd/`

### Opção 2: servidor embutido do PHP

1. Entre na pasta do projeto:
   ```bash
   cd /home/runner/work/tfd/tfd
   ```
2. (Opcional) Recrie o banco do zero:
   ```bash
   php scripts/init_db.php
   ```
3. Inicie o servidor PHP:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
4. Acesse:
   - `http://127.0.0.1:8000/`

## Credenciais demo

- Administrativo: `tfdcolares` / `tfd123`
- Assistente Social: `assistente.social` / `tfd123`
- Médico: `medico.tfd` / `tfd123`
- Auxiliar Administrativo: `auxiliar.adm` / `tfd123`

## Observações de MVP

- Os documentos aceitam PDF, PNG, JPG, JPEG, DOC e DOCX com até 10 MB por arquivo.
- Os arquivos enviados ficam armazenados em `data/uploads/documents/`.
- O banco é inicializado automaticamente na primeira execução caso não exista.
