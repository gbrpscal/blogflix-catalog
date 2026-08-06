# Blogflix Catalog

Catálogo de filmes em SPA integrado ao TMDB, com autenticação por sessão, verificação de e-mail, recuperação de senha, Google OAuth e favoritos por usuário. O repositório é um monorepo monolítico: Laravel expõe uma API REST e Vue consome somente essa API; o token do TMDB nunca chega ao navegador.

> Este produto usa a API do TMDB, mas não é endossado nem certificado pelo TMDB.

## Estado das integrações

A aplicação inicia normalmente sem credenciais do TMDB e do Google. Enquanto elas estiverem vazias, a API informa essas capacidades como desabilitadas e a interface não tenta usar as integrações. Depois de preencher o `.env`, recrie os serviços com `docker compose up -d --build`.

Nunca versione o `.env`. Somente o `.env.example`, sem valores secretos, pertence ao Git.

## Requisitos

- Docker Desktop ou Docker Engine com Docker Compose v2;
- portas locais 8080 e 8025 disponíveis;
- credencial TMDB e credenciais Google somente para habilitar essas integrações.

PHP, Composer, Node.js, npm, PostgreSQL e Redis não precisam estar instalados na máquina.

## Início rápido

1. Copie o arquivo de ambiente:

   ```bash
   cp .env.example .env
   ```

   No PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

2. Gere uma chave Laravel e cole o resultado em `APP_KEY`:

   ```bash
   docker run --rm php:8.4.13-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

3. Troque `DB_PASSWORD=change-me` por uma senha local forte. As credenciais TMDB e Google podem continuar vazias no primeiro início.

4. Inicie tudo:

   ```bash
   docker compose up -d
   ```

O Compose instala dependências durante o build, compila o Vue, aguarda PostgreSQL/Redis, executa migrations, prepara o link de storage e inicia o worker. Não execute manualmente `composer install`, `npm install`, `npm run build`, `php artisan migrate` ou `storage:link`.

Depois do primeiro build:

- aplicação: [http://localhost:8080](http://localhost:8080);
- Mailpit: [http://localhost:8025](http://localhost:8025);
- saúde: [http://localhost:8080/api/v1/health](http://localhost:8080/api/v1/health).

Confira os serviços com:

```bash
docker compose ps
docker compose logs init
```

O serviço `init` terminar com código 0 é esperado.

## Variáveis de ambiente

O `.env.example` é a referência completa. Os grupos mais importantes são:

- aplicação: `APP_KEY`, `APP_URL`, `FRONTEND_URL`, locale e timezone;
- PostgreSQL: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `POSTGRES_TEST_DB`;
- cookies: `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SANCTUM_STATEFUL_DOMAINS`;
- e-mail: `MAIL_*`;
- TMDB: `TMDB_API_TOKEN`, idioma, região, limite das coleções, timeouts e TTLs;
- Google: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.

Para produção, use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, domínio de sessão correto e origens CORS/Sanctum estritamente definidas.

## Credencial do TMDB

1. Crie uma conta no [TMDB](https://www.themoviedb.org/signup).
2. Em configurações da conta, abra a seção API e solicite acesso.
3. Copie o **API Read Access Token** (Bearer), não o exponha como variável Vite.
4. Defina:

   ```dotenv
   TMDB_API_TOKEN=seu_read_access_token
   TMDB_LANGUAGE=pt-BR
   TMDB_REGION=BR
   TMDB_COLLECTIONS_LIMIT=20
   ```

5. Recrie as imagens/containers:

   ```bash
   docker compose up -d --build
   ```

O backend envia o token apenas no header Authorization. Busca, descoberta, coleções, detalhes e gêneros passam pelo Laravel.

## Google OAuth

No [Google Cloud Console](https://console.cloud.google.com/apis/credentials):

1. crie ou selecione um projeto;
2. configure a tela de consentimento OAuth;
3. crie uma credencial **OAuth client ID** do tipo **Web application**;
4. em origens JavaScript autorizadas, adicione `http://localhost:8080`;
5. em URIs de redirecionamento autorizados, adicione exatamente:

   ```text
   http://localhost:8080/api/v1/auth/google/callback
   ```

6. caso o app esteja em modo de teste, adicione os usuários de teste;
7. preencha:

   ```dotenv
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_REDIRECT_URI=http://localhost:8080/api/v1/auth/google/callback
   ```

A aplicação pede somente identidade básica e e-mail, aceita apenas e-mail verificado, vincula contas existentes pelo e-mail e não persiste access/refresh tokens do Google.

Em produção, cadastre a origem e o callback HTTPS reais e atualize `APP_URL`, `FRONTEND_URL` e `GOOGLE_REDIRECT_URI`.

## E-mail e fila

Cadastro, reenvio de verificação e recuperação de senha criam notificações criptografadas na fila Redis `emails`. O serviço `worker` executa `queue:work` continuamente, com três tentativas e backoff. Portanto, recuperação de senha exige:

- Redis saudável;
- worker em execução;
- transporte SMTP configurado;
- `APP_KEY` estável, pois ela criptografa os jobs;
- `APP_URL`/`FRONTEND_URL` corretas para os links.

No ambiente local, nenhuma conta SMTP é necessária:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_SCHEME=
MAIL_USERNAME=
MAIL_PASSWORD=
```

As mensagens aparecem em [http://localhost:8025](http://localhost:8025).

Para SMTP real, use os dados do provedor. Exemplo para STARTTLS:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.exemplo.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=usuario
MAIL_PASSWORD=senha
MAIL_FROM_ADDRESS=no-reply@seudominio.com
MAIL_FROM_NAME=Blogflix
```

Para TLS implícito, normalmente use `MAIL_SCHEME=smtps` e porta 465. Confirme a combinação com o provedor.

Monitore a fila:

```bash
docker compose logs -f worker
docker compose exec backend php artisan queue:failed
```

## Arquitetura

```text
Navegador
  └─ Nginx :8080
      ├─ / e rotas SPA -> Vue compilado
      ├─ /sanctum/*    -> PHP-FPM
      └─ /api/*        -> Laravel
                            ├─ PostgreSQL (usuários, favoritos, jobs)
                            ├─ Redis (cache, sessão, rate limit, fila)
                            ├─ TMDB (somente pelo TmdbClient)
                            ├─ Google OAuth (Socialite)
                            └─ SMTP/Mailpit (pelo worker)
```

Separação de responsabilidades no backend:

- controllers finos coordenam HTTP;
- Form Requests validam entrada;
- Actions/Services concentram regras;
- DTOs normalizam payloads externos;
- API Resources estabilizam respostas;
- Policies aplicam autorização;
- Models representam persistência;
- `TmdbClient` é a única fronteira HTTP com o TMDB.

No frontend:

- Router define navegação e guards apenas de experiência;
- Pinia mantém autenticação somente em memória;
- Axios usa `withCredentials`, cookie de sessão HttpOnly e XSRF;
- views orquestram páginas;
- components são reutilizáveis e acessíveis;
- nenhum token é gravado em localStorage.
- busca mantém nome e gênero em edição até o envio explícito do formulário;
- rota da SPA usa apenas os parâmetros curtos: `q`, `g`, `s` e `p`;
- ordenação é independente, usa Destaques por padrão e reinicia a paginação ao mudar;

## Estrutura de diretórios

```text
.
├── apps/
│   ├── backend/
│   │   ├── app/
│   │   │   ├── Actions/
│   │   │   ├── DTOs/Tmdb/
│   │   │   ├── Http/{Controllers,Requests,Resources,Responses}/
│   │   │   ├── Integrations/Tmdb/
│   │   │   ├── Models/
│   │   │   ├── Notifications/
│   │   │   ├── Policies/
│   │   │   └── Services/Tmdb/
│   │   ├── database/{factories,migrations}/
│   │   ├── routes/{api,web}.php
│   │   └── tests/{Unit,Feature}/
│   └── frontend/
│       ├── src/
│       │   ├── api/
│       │   ├── components/{feedback,forms,layout,movies}/
│       │   ├── router/
│       │   ├── stores/
│       │   ├── styles/
│       │   ├── types/
│       │   └── views/
│       └── tests/
├── docker/
│   ├── nginx/
│   ├── php/
│   ├── postgres/init/
│   └── redis/
├── compose.yaml
├── DEVELOPMENT.md
└── .env.example
```

## Serviços Docker

| Serviço | Função | Exposição |
|---|---|---|
| `nginx` | Vue, fallback SPA e proxy FastCGI | 127.0.0.1:8080 |
| `backend` | Laravel em PHP-FPM | somente rede interna |
| `init` | migrations e preparação de storage | execução única |
| `worker` | fila Redis `emails,default` | somente rede interna |
| `postgres` | dados da aplicação e banco isolado de teste | somente rede interna |
| `redis` | cache, sessão, rate limit e fila | somente rede interna |
| `mailpit` | SMTP local e caixa web | 127.0.0.1:8025 |
| `backend-test` | Pest/Pint, profile `tools` | sob demanda |
| `frontend-tooling` | Vitest/ESLint/Prettier/TypeScript | sob demanda |

PostgreSQL e Redis usam volumes persistentes. Todos os serviços críticos possuem healthcheck, e as dependências aguardam saúde ou conclusão bem-sucedida.

## Banco de dados

`users` contém os campos Laravel de autenticação, `google_id` único e opcional e `avatar_url` opcional.

`favorites` contém:

- `id`, `user_id`, `tmdb_id`;
- `title`, `overview`, `poster_path`, `release_date`;
- `genre_ids` como JSONB;
- timestamps;
- FK para `users` com cascade delete;
- índice por usuário;
- índice GIN para filtro de gêneros;
- unique `(user_id, tmdb_id)`.

A restrição única do PostgreSQL é a última defesa contra concorrência; a API transforma a violação em resposta 409. O usuário nunca fornece o snapshot do filme: o backend consulta o TMDB antes de persistir.

## API REST v1

Endpoints públicos:

| Método | Endpoint | Finalidade |
|---|---|---|
| GET | `/api/v1/health` | saúde de aplicação, banco e Redis |
| GET | `/api/v1/meta` | capacidades Google/TMDB sem expor segredos |
| GET | `/sanctum/csrf-cookie` | inicializa proteção CSRF da SPA |
| POST | `/api/v1/auth/register` | cadastro |
| POST | `/api/v1/auth/login` | login |
| POST | `/api/v1/auth/forgot-password` | solicita recuperação |
| POST | `/api/v1/auth/reset-password` | redefine senha |
| GET | `/api/v1/auth/google/redirect` | inicia OAuth |
| GET | `/api/v1/auth/google/callback` | callback OAuth |

Endpoints autenticados:

| Método | Endpoint | Regra |
|---|---|---|
| POST | `/api/v1/auth/logout` | encerra sessão |
| GET | `/api/v1/auth/user` | usuário atual |
| POST | `/api/v1/auth/email/verification-notification` | reenvia verificação |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | link assinado de verificação |

Endpoints que também exigem e-mail verificado:

| Método | Endpoint | Entrada |
|---|---|---|
| GET | `/api/v1/movies` | `query` opcional, `page`, `genre_id`, `sort` |
| GET | `/api/v1/movies/collections` | coleções popular, melhores avaliações, lançamentos e em alta |
| GET | `/api/v1/movies/{tmdbId}` | ID TMDB |
| GET | `/api/v1/genres` | — |
| GET | `/api/v1/favorites` | `genre_id`, `page`, `per_page` |
| POST | `/api/v1/favorites` | `{"tmdb_id": 550}` |
| DELETE | `/api/v1/favorites/{favorite}` | ID interno do favorito |

Exemplo de catálogo inicial paginado e filtrado:

```http
GET /api/v1/movies?page=1&genre_id=18&sort=highlights
Accept: application/json
```

Exemplo de busca por título, com ordenação da janela de resultados:

```http
GET /api/v1/movies?query=matrix&page=1&genre_id=878&sort=title_asc
Accept: application/json
```

Exemplo de favorito:

```http
POST /api/v1/favorites
Content-Type: application/json
Accept: application/json

{"tmdb_id": 603}
```

Respostas de coleção seguem o envelope do Laravel Resource, com `data`, `links` e `meta`. Validação retorna 422, duplicidade 409, não autenticado 401, e recurso de outro usuário é tratado como 404.

## Fluxo de autenticação SPA

1. Axios solicita `/sanctum/csrf-cookie`;
2. o navegador recebe `XSRF-TOKEN` e cookie de sessão;
3. cadastro/login envia o header X-XSRF-TOKEN automaticamente;
4. Laravel autentica pela sessão armazenada no Redis;
5. a SPA consulta `/api/v1/auth/user`;
6. rotas de catálogo exigem `auth:sanctum` e `verified`;
7. logout invalida a sessão no backend.

Os guards Vue melhoram a navegação, mas não são controle de acesso. Policies e middlewares Laravel aplicam todas as regras.

## Cache e resiliência do TMDB

- pesquisas: 600 s por padrão;
- descoberta paginada: 600 s por padrão;
- detalhes: 3600 s;
- gêneros: 86400 s;
- coleções da home: 1800 s;
- páginas brutas de busca são cacheadas por idioma, região, página e query normalizada/hasheada;
- descoberta mantém chaves próprias por página, filtro e ordenação;
- Redis usa conexão separada para cache;
- timeout de conexão: 3 s;
- timeout total: 8 s;
- retry limitado somente para falhas transitórias;
- erros externos viram resposta 502/503 controlada;
- token nunca é incluído em mensagens de log.

Altere os TTLs com `TMDB_*_CACHE_TTL` e o número de filmes por carrossel com `TMDB_COLLECTIONS_LIMIT` (máximo 20). Os testes usam `Http::fake()` e nunca acessam o TMDB real.

## Telas e componentes

Views: login, cadastro, esqueci a senha, redefinição, aviso de verificação, home de descoberta/busca, favoritos/filtro e 404.

Componentes principais: `MovieCard`, `MovieCarousel`, `SearchField`, `AppPagination`, `FormField`, `ErrorMessage`, `LoadingIndicator`, `ConfirmDialog` e `AppHeader`.

O layout é responsivo, possui navegação por teclado, labels, estados de foco, mensagens anunciáveis, loading, vazios e erros.

## Tecnologias e versões fixadas

Infra:

- PHP FPM 8.4.13;
- Composer 2.8.12;
- Node.js 22.19.0;
- Nginx 1.28.0;
- PostgreSQL 17.6;
- Redis 8.2.1;
- Mailpit 1.27.8.

Backend instalado no lockfile:

- Laravel Framework 12.65.0;
- Fortify 1.37.3;
- Sanctum 4.3.3;
- Socialite 5.29.0;
- Pest 3.8.7;
- Pest Laravel Plugin 3.2.0;
- PHPUnit 11.5.56;
- Pint 1.30.4.

Frontend:

- Vue 3.5.41;
- Vue Router 4.6.4;
- Pinia 3.0.4;
- Axios 1.19.0;
- Vite 7.3.6;
- TypeScript 5.8.3;
- Vitest 4.1.10;
- ESLint 10.8.0;
- Prettier 3.9.6.

As versões transitivas completas estão em `composer.lock` e `package-lock.json`.

## Testes e qualidade

Backend, em PostgreSQL isolado:

```bash
docker compose --profile tools run --rm backend-test php artisan test --compact
docker compose --profile tools run --rm backend-test vendor/bin/pint --test
```

Frontend:

```bash
docker compose --profile tools run --rm frontend-tooling npm test
docker compose --profile tools run --rm frontend-tooling npm run type-check
docker compose --profile tools run --rm frontend-tooling npm run lint
docker compose --profile tools run --rm frontend-tooling npm run format:check
```

Build de produção:

```bash
docker compose build backend worker nginx
```

A cobertura backend inclui DTO unitário, favoritos, autorização, filtro JSONB, validação, cache/erros e janela de busca TMDB, cadastro, sessão, verificação, reset e OAuth falso. O frontend cobre cards, carrossel por setas, paginação, URL curta, formulário de busca e store de autenticação.

## Padrão de desenvolvimento

Consulte [DEVELOPMENT.md](DEVELOPMENT.md) antes de alterar contratos, banco ou infraestrutura. Ele define limites das camadas, convenções, estratégia de testes, checklist e commits semânticos.

## Decisões técnicas

- PostgreSQL substitui MySQL e permite JSONB + índice GIN para gêneros.
- Sanctum usa cookies/sessão; não há JWT, Passport, token em localStorage nem Inertia.
- Fortify fornece autenticação headless; Vue é responsável pelas telas.
- Snapshot mínimo do filme preserva favoritos se os dados externos mudarem.
- Gêneros não são duplicados em tabela local; o catálogo do TMDB fica cacheado.
- “Mais assistidos” usa a lista de popularidade do TMDB, pois a API não fornece contagem pública real de visualizações.
- E-mails são jobs criptografados, mantendo requests de cadastro/reset rápidos.
- Nginx é a única porta pública da aplicação e bloqueia arquivos sensíveis do Laravel.
- Perfis `tools` mantêm dependências de desenvolvimento fora da imagem PHP de runtime.

## Limitações conhecidas

- credenciais Google/TMDB precisam ser criadas e inseridas pelo responsável;
- não há testes E2E em navegador nem pipeline CI neste repositório;
- não há painel para reprocessar jobs falhos;
- na busca textual, gênero e ordenação são aplicados a uma janela de duas páginas adjacentes do TMDB; a descoberta sem texto usa filtros globais nativos;
- a disponibilidade e as imagens dos filmes dependem do TMDB;
- a imagem Nginx é estática: mudanças Vue exigem rebuild;
- produção exige domínio, HTTPS, SMTP real e revisão dos limites conforme a carga.

## Solução de problemas

**A aplicação não inicia:** confira `APP_KEY`, `DB_PASSWORD` e `docker compose logs init backend`.

**Banco acusa senha inválida após trocar o `.env`:** a senha inicial fica no volume. Em desenvolvimento, recrie os volumes apenas se puder apagar os dados:

```bash
docker compose down -v
docker compose up -d
```

**Login retorna 419:** acesse tudo pelo mesmo host/porta, revise `APP_URL`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN` e cookies HTTPS.

**E-mail não chega:** verifique `docker compose ps worker redis mailpit`, `docker compose logs worker` e a UI do Mailpit. Jobs antigos não podem ser descriptografados se `APP_KEY` mudou.

**TMDB aparece desabilitado:** preencha `TMDB_API_TOKEN` e rode `docker compose up -d --build`.

**Google retorna redirect_uri_mismatch:** o callback no Google deve ser idêntico a `GOOGLE_REDIRECT_URI`, incluindo protocolo, host, porta e caminho.

**Dependências frontend do profile tools ficaram antigas:** após alterar `package-lock.json`, remova somente o volume de node_modules e reconstrua o tooling:

```bash
docker compose --profile tools stop frontend-tooling
docker volume rm blogflix_frontend_node_modules
docker compose --profile tools build frontend-tooling
```

**Inspecionar rotas/migrations:**

```bash
docker compose exec backend php artisan route:list --path=api/v1
docker compose exec backend php artisan migrate:status
```
