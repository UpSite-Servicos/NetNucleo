# SISGED — Sistema de Gestão Educacional Dinâmico

Sistema de gerenciamento de turmas, salas, instrutores, alunos e aulas, com
login por perfil (Coordenação, Instrutor, Aluno) e relatórios.

## Tecnologias
- PHP puro (PDO + MySQL)
- MySQL / MariaDB
- Bootstrap 5 (CSS) + JavaScript puro
- Ambiente local: **WAMP**

---

## 1. Instalação passo a passo (WAMP)

### 1.1. Copiar o projeto
Copie a pasta inteira `sisged` para dentro de:
```
C:\wamp64\www\sisged
```
(o nome da pasta dentro de `www` **precisa** ser `sisged`, pois o sistema usa
esse caminho fixo em `includes/functions.php` → função `caminhoBase()`)

### 1.2. Ligar o WAMP
Abra o WAMP e espere o ícone da bandeja do Windows ficar **verde**
(significa que o Apache e o MySQL estão rodando).

### 1.3. Criar o banco de dados
1. Acesse `http://localhost/phpmyadmin`
2. Clique em **Importar**
3. Selecione o arquivo `sql/sisged.sql` (dentro da pasta do projeto)
4. Clique em **Executar**

Isso cria o banco `sisged` com todas as tabelas e já insere dados de
exemplo (instrutores, alunos, salas, turmas e aulas).

### 1.4. Criar os usuários de login
As senhas precisam ser geradas pelo próprio PHP, então rode uma vez, pelo
navegador:
```
http://localhost/sisged/sql/seed_usuarios.php
```
Isso cria 3 contas de teste, todas com senha `123456`:

| E-mail                          | Perfil       |
|----------------------------------|--------------|
| coordenacao@sisged.com.br        | Coordenação  |
| carlos.andrade@sisged.com.br     | Instrutor    |
| ana.santos@aluno.sisged.com.br   | Aluno        |

**Depois de rodar uma vez, apague o arquivo `sql/seed_usuarios.php`** (ou
pelo menos não deixe ele acessível em produção) — ele é só uma ferramenta
de instalação.

### 1.5. Acessar o sistema
```
http://localhost/sisged/
```
Você será redirecionado para a tela de login.

---

## 2. Se o MySQL do seu WAMP tiver senha

Por padrão o WAMP usa usuário `root` sem senha. Se o seu ambiente tiver
senha configurada, edite:
```
config/database.php
```
e ajuste a constante `DB_PASS`.

---

## 3. Estrutura de pastas

```
sisged/
├── auth/                → login.php, logout.php
├── config/               → database.php (conexão PDO)
├── includes/             → functions.php (auth, helpers, checagem de conflito de agenda)
│                           header.php / footer.php (layout compartilhado)
├── instrutores/          → listar / formulario / excluir
├── alunos/               → listar / formulario / excluir (inclui matrícula em turmas)
├── salas/                → listar / formulario / excluir
├── turmas/                → listar / formulario / excluir
├── aulas/                 → listar / formulario / excluir (agenda com checagem de conflito)
├── relatorios/            → index.php (filtros por período, sala, instrutor, turma)
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── sql/
│   ├── sisged.sql         → cria banco + dados de exemplo
│   └── seed_usuarios.php  → cria usuários de login (rodar 1x)
├── dashboard.php
├── index.php
└── README.md
```

## 4. Perfis de acesso

- **Coordenação**: acesso total (CRUD de instrutores, alunos, salas, turmas,
  aulas + relatórios de todo o sistema).
- **Instrutor**: vê apenas suas turmas e aulas (somente leitura) + relatório
  restrito às próprias aulas.
- **Aluno**: vê apenas as turmas em que está matriculado e as aulas dessas
  turmas (somente leitura).

## 5. Sobre a checagem de conflito de agenda

Ao cadastrar/editar uma aula, o sistema verifica — dentro de uma transação
no banco — se a **sala** ou o **instrutor** já têm outra aula com horário
sobreposto naquela data (não apenas horário idêntico). Isso está implementado
em `includes/functions.php`, na função `verificarConflitoAula()`, usada por
`aulas/formulario.php`.

## 6. Banco de dados (resumo do modelo)

- `usuarios` — login (perfil: aluno / instrutor / coordenacao)
- `instrutores`, `alunos`, `salas`, `turmas` — cadastros básicos
- `matriculas` — relação N:N entre alunos e turmas
- `aulas` — agenda (liga turma + sala + instrutor + data/horário)
