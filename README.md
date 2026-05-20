# Sistema TFD (Transporte Fora do Domicílio)

Aplicação web em **PHP + CSS + JavaScript** para gestão operacional e administrativa de TFD.

## Funcionalidades implementadas

- **Autenticação e sessão**
  - Tela de login
  - Usuário demo: `tfdcolares` / senha `tfd123`
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
  - Estrutura de anexos implementada para paciente, acompanhante e processo
  - MVP registra metadados e já deixa UX/modelagem pronta para upload real
- **Fluxo operacional e status**
  - Status: cadastrado, aguardando análise, pendente de documentos, autorizado, agendado, em viagem, retornado, concluído, negado, cancelado
  - Página com fluxo em **SVG**
- **Viagens**
  - Agendamento e acompanhamento de execução/retorno/conclusão

## Stack

- PHP 8+
- SQLite (persistência real)
- CSS e JavaScript nativos

## Estrutura do projeto

- `public/index.php` → front controller e rotas por página
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

## Como executar localmente

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
   - `http://127.0.0.1:8000/index.php?page=login`

## Credenciais demo

- Usuário: `tfdcolares`
- Senha: `tfd123`

## Observações de MVP

- Upload de arquivo real em documentos não foi ativado no MVP, mas a estrutura de dados e UX já estão prontas para evoluir.
- O banco é inicializado automaticamente na primeira execução caso não exista.
